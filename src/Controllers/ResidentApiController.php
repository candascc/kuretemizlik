<?php

/**
 * Resident API Controller
 * Mobil uygulama için sakin API uçları
 */
class ResidentApiController
{
    private $residentUserModel;
    private $residentRequestModel;
    private $managementFeeModel;
    private $buildingModel;
    private $unitModel;
    private $documentModel;
    private $jwtAuth;

    public function __construct()
    {
        $this->residentUserModel = new ResidentUser();
        $this->residentRequestModel = new ResidentRequest();
        $this->managementFeeModel = new ManagementFee();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
        $this->documentModel = new BuildingDocument();
        $this->jwtAuth = new JWTAuth();
    }

    /**
     * Resident login
     */
    public function login()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            if (empty($email) || empty($password)) {
                $this->jsonResponse(['error' => 'E-posta ve şifre gereklidir'], 400);
                return;
            }

            $resident = $this->residentUserModel->findByEmail($email);
            
            $passwordHash = (string)($resident['password_hash'] ?? '');
            if (!$resident || empty($passwordHash) || !password_verify($password, $passwordHash)) {
                $this->jsonResponse(['error' => 'Geçersiz e-posta veya şifre'], 401);
                return;
            }

            // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
            if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                if ($newHash) {
                    try {
                        $this->residentUserModel->updatePassword($resident['id'], $password);
                    } catch (Exception $e) {
                        // Log but don't fail login if rehash update fails
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Password rehash failed for resident {$resident['id']}: " . $e->getMessage());
                        }
                    }
                }
            }
            // ===== ERR-014 FIX: End =====

            if (!$resident['is_active']) {
                $this->jsonResponse(['error' => 'Hesabınız aktif değil'], 401);
                return;
            }

            // Generate JWT token
            $token = $this->jwtAuth->generateToken([
                'resident_id' => $resident['id'],
                'unit_id' => $resident['unit_id'],
                'email' => $resident['email']
            ]);

            // Update last login
            $this->residentUserModel->update($resident['id'], [
                'last_login_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'token' => $token,
                'resident' => [
                    'id' => $resident['id'],
                    'name' => $resident['name'],
                    'email' => $resident['email'],
                    'phone' => $resident['phone'],
                    'unit_id' => $resident['unit_id']
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Giriş hatası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get resident profile
     */
    public function profile()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unit = $this->unitModel->find($resident['unit_id']);
        $building = $this->buildingModel->find($unit['building_id']);

        $this->jsonResponse([
            'success' => true,
            'resident' => [
                'id' => $resident['id'],
                'name' => $resident['name'],
                'email' => $resident['email'],
                'phone' => $resident['phone'],
                'is_owner' => $resident['is_owner'],
                'email_verified' => $resident['email_verified'],
                'last_login_at' => $resident['last_login_at'],
                'unit' => [
                    'id' => $unit['id'],
                    'unit_number' => $unit['unit_number'],
                    'unit_type' => $unit['unit_type'],
                    'floor_number' => $unit['floor_number'],
                    'gross_area' => $unit['gross_area'],
                    'net_area' => $unit['net_area'],
                    'room_count' => $unit['room_count'],
                    'monthly_fee' => $unit['monthly_fee'],
                    'debt_balance' => $unit['debt_balance']
                ],
                'building' => [
                    'id' => $building['id'],
                    'name' => $building['name'],
                    'address_line' => $building['address_line'],
                    'city' => $building['city'],
                    'district' => $building['district'],
                    'manager_name' => $building['manager_name'],
                    'manager_phone' => $building['manager_phone'],
                    'manager_email' => $building['manager_email']
                ]
            ]
        ]);
    }

    /**
     * Get dashboard data
     */
    public function dashboard()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unitId = $resident['unit_id'];
        $unit = $this->unitModel->find($unitId);
        $buildingId = $unit['building_id'];

        // Get recent fees
        $recentFees = $this->managementFeeModel->list([
            'unit_id' => $unitId
        ], 5, 0);

        // Get pending requests
        $pendingRequests = $this->residentRequestModel->list([
            'unit_id' => $unitId,
            'status' => 'open'
        ], 5, 0);

        // Get building announcements
        $announcements = $this->getBuildingAnnouncements($buildingId);

        // Get upcoming meetings
        $meetings = $this->getUpcomingMeetings($buildingId);

        $this->jsonResponse([
            'success' => true,
            'dashboard' => [
                'recent_fees' => $recentFees,
                'pending_requests' => $pendingRequests,
                'announcements' => $announcements,
                'meetings' => $meetings,
                'stats' => [
                    'pending_fees' => count(array_filter($recentFees, function($f) { return $f['status'] === 'pending'; })),
                    'open_requests' => count($pendingRequests),
                    'new_announcements' => count($announcements),
                    'upcoming_meetings' => count($meetings)
                ]
            ]
        ]);
    }

    /**
     * Get management fees
     */
    public function fees()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unitId = $resident['unit_id'];
        $page = (int)($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? '';

        $filters = ['unit_id' => $unitId];
        if ($status) $filters['status'] = $status;

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $fees = $this->managementFeeModel->list($filters, $limit, $offset);

        $this->jsonResponse([
            'success' => true,
            'fees' => $fees,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($fees)
            ]
        ]);
    }

    /**
     * Pay management fee
     */
    public function payFee($feeId)
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $amount = (float)($input['amount'] ?? 0);
            $method = $input['payment_method'] ?? 'cash';
            $notes = $input['notes'] ?? '';

            $fee = $this->managementFeeModel->find($feeId);
            if (!$fee) {
                $this->jsonResponse(['error' => 'Aidat bulunamadı'], 404);
                return;
            }

            // Check if fee belongs to resident's unit
            if ($fee['unit_id'] != $resident['unit_id']) {
                $this->jsonResponse(['error' => 'Bu aidat sizin dairenize ait değil'], 403);
                return;
            }

            $remainingAmount = $fee['total_amount'] - $fee['paid_amount'];
            if ($amount <= 0 || $amount > $remainingAmount) {
                $this->jsonResponse(['error' => 'Geçersiz ödeme tutarı'], 400);
                return;
            }

            // Apply payment
            $result = $this->managementFeeModel->applyPayment($feeId, $amount, $method, date('Y-m-d'), $notes);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Ödeme başarıyla kaydedildi',
                'fee' => $result['fee'] ?? $this->managementFeeModel->find($feeId),
                'transaction' => [
                    'reference' => $result['reference'] ?? null,
                    'money_entry_id' => $result['money_entry_id'] ?? null,
                    'status' => $result['status'] ?? null,
                    'amount' => $result['amount'] ?? $amount,
                    'method' => $result['method'] ?? $method,
                ],
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Ödeme işlemi başarısız: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get requests
     */
    public function requests()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unitId = $resident['unit_id'];
        $page = (int)($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? '';

        $filters = ['unit_id' => $unitId];
        if ($status) $filters['status'] = $status;

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $requests = $this->residentRequestModel->list($filters, $limit, $offset);

        $this->jsonResponse([
            'success' => true,
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($requests)
            ]
        ]);
    }

    /**
     * Create request
     */
    public function createRequest()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $unit = $this->unitModel->find($resident['unit_id']);
            
            $data = [
                'building_id' => $unit['building_id'],
                'unit_id' => $resident['unit_id'],
                'resident_user_id' => $resident['id'],
                'request_type' => $input['request_type'] ?? 'other',
                'category' => $input['category'] ?? '',
                'subject' => $input['subject'] ?? '',
                'description' => $input['description'] ?? '',
                'priority' => $input['priority'] ?? 'normal'
            ];

            if (empty($data['subject']) || empty($data['description'])) {
                $this->jsonResponse(['error' => 'Konu ve açıklama gereklidir'], 400);
                return;
            }

            $requestId = $this->residentRequestModel->create($data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Talebiniz başarıyla oluşturuldu',
                'request_id' => $requestId
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Talep oluşturulamadı: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get announcements
     */
    public function announcements()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unit = $this->unitModel->find($resident['unit_id']);
        $buildingId = $unit['building_id'];

        $announcements = $this->getBuildingAnnouncements($buildingId);

        $this->jsonResponse([
            'success' => true,
            'announcements' => $announcements
        ]);
    }

    /**
     * Get meetings
     */
    public function meetings()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unit = $this->unitModel->find($resident['unit_id']);
        $buildingId = $unit['building_id'];

        $meetings = $this->getUpcomingMeetings($buildingId);

        $this->jsonResponse([
            'success' => true,
            'meetings' => $meetings
        ]);
    }

    /**
     * Get documents
     */
    public function documents()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        $unit = $this->unitModel->find($resident['unit_id']);
        $buildingId = $unit['building_id'];

        // Get public building documents and unit-specific documents
        $buildingDocs = $this->documentModel->getByBuilding($buildingId, true);
        $unitDocs = $this->documentModel->getByUnit($resident['unit_id']);

        $documents = array_merge($buildingDocs, $unitDocs);

        $this->jsonResponse([
            'success' => true,
            'documents' => $documents
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile()
    {
        $resident = $this->requireResidentAuth();
        if (!$resident) return;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $data = [
                'name' => $input['name'] ?? $resident['name'],
                'phone' => $input['phone'] ?? $resident['phone'],
                'email' => $input['email'] ?? $resident['email']
            ];

            if (empty($data['name']) || empty($data['email'])) {
                $this->jsonResponse(['error' => 'Ad ve e-posta gereklidir'], 400);
                return;
            }

            $this->residentUserModel->update($resident['id'], $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Profil güncellendi',
                'resident' => $this->residentUserModel->find($resident['id'])
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Profil güncellenemedi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper methods
     */
    private function requireResidentAuth()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            $this->jsonResponse(['error' => 'Token gereklidir'], 401);
            return null;
        }

        $payload = $this->jwtAuth->validateToken($token);
        if (!$payload || !isset($payload['resident_id'])) {
            $this->jsonResponse(['error' => 'Geçersiz token'], 401);
            return null;
        }

        $resident = $this->residentUserModel->find($payload['resident_id']);
        if (!$resident || !$resident['is_active']) {
            $this->jsonResponse(['error' => 'Kullanıcı bulunamadı'], 401);
            return null;
        }

        return $resident;
    }

    private function getBearerToken()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function getBuildingAnnouncements($buildingId)
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM building_announcements 
             WHERE building_id = ? AND (expire_date IS NULL OR expire_date >= date('now'))
             ORDER BY priority DESC, publish_date DESC 
             LIMIT 10",
            [$buildingId]
        );
    }

    private function getUpcomingMeetings($buildingId)
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM building_meetings 
             WHERE building_id = ? AND meeting_date >= date('now') AND status = 'scheduled'
             ORDER BY meeting_date ASC 
             LIMIT 5",
            [$buildingId]
        );
    }
}
