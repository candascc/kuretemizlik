<?php

declare(strict_types=1);

/**
 * Staff Controller
 * 
 * Handles staff-related operations including CRUD operations,
 * job assignments, attendance tracking, and payment management.
 * 
 * @package App\Controllers
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class StaffController
{
    use CompanyScope;

    public function index()
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of staff list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        $staff = Staff::getAll();
        echo View::renderWithLayout('staff/list', ['staff' => $staff]);
    }

    public function create()
    {
        Auth::require();
        Utils::setNoCacheHeaders();
        echo View::renderWithLayout('staff/form');
    }

    public function store()
    {
        Auth::requireAdmin();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/staff')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/staff', 'Geçersiz istek')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $data = [
            'name' => InputSanitizer::string($_POST['name'] ?? '', 100),
            'surname' => InputSanitizer::string($_POST['surname'] ?? '', 100),
            'phone' => InputSanitizer::phone($_POST['phone'] ?? null),
            'email' => InputSanitizer::email($_POST['email'] ?? null),
            // Treat empty TC as NULL to avoid UNIQUE collisions on empty strings
            'tc_number' => InputSanitizer::string($_POST['tc_number'] ?? null, 11) ?: null,
            'birth_date' => InputSanitizer::date($_POST['birth_date'] ?? null),
            'address' => InputSanitizer::string($_POST['address'] ?? '', 500),
            'position' => InputSanitizer::string($_POST['position'] ?? '', 100),
            'hire_date' => InputSanitizer::date($_POST['hire_date'] ?? null) ?: date('Y-m-d'),
            'salary' => InputSanitizer::float($_POST['salary'] ?? 0, 0, 999999.99) ?? 0.0,
            'hourly_rate' => InputSanitizer::float($_POST['hourly_rate'] ?? 0, 0, 9999.99) ?? 0.0,
            'notes' => InputSanitizer::string($_POST['notes'] ?? '', 1000),
            'status' => in_array($_POST['status'] ?? 'active', ['active', 'inactive', 'terminated']) ? $_POST['status'] : 'active'
        ];

        // Email kontrolü
        if (!empty($data['email']) && Staff::findByEmail($data['email'])) {
            ControllerHelper::flashErrorAndRedirect('Bu email adresi zaten kayıtlı.', '/staff/new');
            return;
        }

        // TC Kimlik kontrolü
        if (!empty($data['tc_number']) && Staff::findByTcNumber($data['tc_number'])) {
            ControllerHelper::flashErrorAndRedirect('Bu TC kimlik numarası zaten kayıtlı.', '/staff/new');
            return;
        }

        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (Staff::create($data)) {
                ControllerHelper::flashSuccessAndRedirect('Personel başarıyla eklendi', '/staff');
            } else {
                ControllerHelper::flashErrorAndRedirect('Personel eklenirken hata oluştu', '/staff');
            }
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'StaffController::store()', 'Personel eklenirken bir hata oluştu', '/staff');
        }
        // ===== ERR-010 FIX: End =====
    }

    public function edit($id)
    {
        Auth::requireAdmin();
        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        echo View::renderWithLayout('staff/form', ['staff' => $staff, 'isEdit' => true]);
    }

    public function update($id)
    {
        Auth::requireAdmin();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $data = [
            'name' => InputSanitizer::string($_POST['name'] ?? '', 100),
            'surname' => InputSanitizer::string($_POST['surname'] ?? '', 100),
            'phone' => InputSanitizer::phone($_POST['phone'] ?? null),
            'email' => InputSanitizer::email($_POST['email'] ?? null),
            'tc_number' => InputSanitizer::string($_POST['tc_number'] ?? null, 11) ?: null,
            'birth_date' => InputSanitizer::date($_POST['birth_date'] ?? null),
            'address' => InputSanitizer::string($_POST['address'] ?? '', 500),
            'position' => InputSanitizer::string($_POST['position'] ?? '', 100),
            'hire_date' => InputSanitizer::date($_POST['hire_date'] ?? null) ?: date('Y-m-d'),
            'salary' => InputSanitizer::float($_POST['salary'] ?? 0, 0, 999999.99) ?? 0.0,
            'hourly_rate' => InputSanitizer::float($_POST['hourly_rate'] ?? 0, 0, 9999.99) ?? 0.0,
            'notes' => InputSanitizer::string($_POST['notes'] ?? '', 1000),
            'status' => in_array($_POST['status'] ?? 'active', ['active', 'inactive', 'terminated']) ? $_POST['status'] : 'active'
        ];

        // Email kontrolü (kendi kaydı hariç)
        if (!empty($data['email'])) {
            $existingStaff = Staff::findByEmail($data['email']);
            if ($existingStaff && $existingStaff['id'] != $id) {
                Utils::flash('error', 'Bu email adresi zaten kayıtlı.');
                redirect(base_url("/staff/edit/$id"));
            }
        }

        // TC Kimlik kontrolü (kendi kaydı hariç)
        if (!empty($data['tc_number'])) {
            $existingStaff = Staff::findByTcNumber($data['tc_number']);
            if ($existingStaff && $existingStaff['id'] != $id) {
                Utils::flash('error', 'Bu TC kimlik numarası zaten kayıtlı.');
                redirect(base_url("/staff/edit/$id"));
            }
        }

        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (Staff::update($id, $data)) {
                Utils::flash('success', 'Personel bilgileri güncellendi');
            } else {
                Utils::flash('error', 'Güncelleme sırasında hata oluştu');
            }
        } catch (Exception $e) {
            error_log("StaffController::update() error: " . $e->getMessage());
            Utils::flash('error', 'Personel güncellenirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====

        // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
    }

    public function delete($id)
    {
        Auth::requireAdmin();
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz personel ID.');
            redirect(base_url('/staff'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        // ===== IMPROVEMENT: Use ControllerHelper for POST and CSRF checks =====
        if (!ControllerHelper::requirePostOrRedirect('/staff')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/staff')) {
            return;
        }
        // ===== IMPROVEMENT: End =====

        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (Staff::delete($id)) {
                Utils::flash('success', 'Personel silindi');
            } else {
                Utils::flash('error', 'Silme işlemi sırasında hata oluştu');
            }
        } catch (Exception $e) {
            error_log("StaffController::delete() error: " . $e->getMessage());
            Utils::flash('error', 'Personel silinirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====

        // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
    }

    public function attendance($id)
    {
        Auth::require();
        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $month = InputSanitizer::date($_GET['month'] ?? null, 'Y-m') ?: date('Y-m');
        $attendance = StaffAttendance::getByStaffAndMonth($id, $month);
        
        echo View::renderWithLayout('staff/attendance', [
            'staff' => $staff,
            'attendance' => $attendance,
            'currentMonth' => $month
        ]);
    }

    public function checkIn($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            redirect('/staff/attendance/' . $id);
        }

        $today = date('Y-m-d');
        $now = date('H:i:s');

        $attendance = StaffAttendance::getByStaffAndDate($id, $today);
        
        if ($attendance) {
            if ($attendance['check_out']) {
                Utils::flash('error', 'Bu personel bugün zaten çıkış yapmış');
            } else {
                // Çıkış yap
                StaffAttendance::update($attendance['id'], ['check_out' => $now]);
                Utils::flash('success', 'Çıkış kaydedildi');
            }
        } else {
            // Giriş yap
            StaffAttendance::create([
                'staff_id' => $id,
                'date' => $today,
                'check_in' => $now,
                'status' => 'present'
            ]);
            Utils::flash('success', 'Giriş kaydedildi');
        }

        redirect('/staff/attendance/' . $id);
    }

    public function assignments($id)
    {
        Auth::require();
        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        $assignments = StaffJobAssignment::getByStaff($id);
        $jobs = Job::getAll();
        
        echo View::renderWithLayout('staff/assignments', [
            'staff' => $staff,
            'assignments' => $assignments,
            'jobs' => $jobs
        ]);
    }

    public function assignJob($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            redirect('/staff/assignments/' . $id);
        }

        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            redirect(base_url('/staff'));
        }

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        // ===== ERR-011 FIX: Add min/max validation =====
        $jobId = InputSanitizer::int($_POST['job_id'] ?? null, 1);
        $job = $jobId ? (new Job())->find($jobId) : null;
        if ($jobId && !$job) {
            Utils::flash('error', 'İş bulunamadı.');
            redirect('/staff/assignments/' . $id);
        }

        $data = [
            'staff_id' => $id,
            'job_id' => $jobId ?: null,
            'assigned_date' => InputSanitizer::date($_POST['assigned_date'] ?? null) ?: date('Y-m-d'),
            'start_time' => InputSanitizer::string($_POST['start_time'] ?? '', 10),
            'end_time' => InputSanitizer::string($_POST['end_time'] ?? '', 10),
            'hourly_rate' => InputSanitizer::float($_POST['hourly_rate'] ?? 0, 0, 9999.99) ?? 0.0,
            'notes' => InputSanitizer::string($_POST['notes'] ?? '', 1000)
        ];

        if (StaffJobAssignment::create($data)) {
            Utils::flash('success', 'İş ataması yapıldı');
        } else {
            Utils::flash('error', 'İş ataması sırasında hata oluştu');
        }

        redirect('/staff/assignments/' . $id);
    }

    public function payments($id)
    {
        Auth::require();
        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        $payments = StaffPayment::getByStaff($id);
        $balances = StaffBalance::getByStaff($id);
        
        echo View::renderWithLayout('staff/payments', [
            'staff' => $staff,
            'payments' => $payments,
            'balances' => $balances
        ]);
    }

    public function addPayment($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            redirect('/staff/payments/' . $id);
        }

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $data = [
            'staff_id' => $id,
            'payment_date' => InputSanitizer::date($_POST['payment_date'] ?? null) ?: date('Y-m-d'),
            'amount' => InputSanitizer::float($_POST['amount'] ?? 0, 0, 999999.99) ?? 0.0,
            'payment_type' => in_array($_POST['payment_type'] ?? 'salary', ['salary', 'bonus', 'advance', 'other']) ? $_POST['payment_type'] : 'salary',
            'description' => InputSanitizer::string($_POST['description'] ?? '', 500),
            'reference_number' => InputSanitizer::string($_POST['reference_number'] ?? '', 100),
            'status' => in_array($_POST['status'] ?? 'pending', ['pending', 'paid', 'cancelled']) ? $_POST['status'] : 'pending'
        ];

        if (StaffPayment::create($data)) {
            Utils::flash('success', 'Ödeme kaydedildi');
        } else {
            Utils::flash('error', 'Ödeme kaydedilirken hata oluştu');
        }

        redirect('/staff/payments/' . $id);
    }

    public function addBalance($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            redirect('/staff/payments/' . $id);
        }

        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $data = [
            'staff_id' => $id,
            'balance_type' => in_array($_POST['balance_type'] ?? 'receivable', ['receivable', 'payable']) ? $_POST['balance_type'] : 'receivable',
            'amount' => InputSanitizer::float($_POST['amount'] ?? 0, 0, 999999.99) ?? 0.0,
            'description' => InputSanitizer::string($_POST['description'] ?? '', 500),
            'due_date' => InputSanitizer::date($_POST['due_date'] ?? null),
            'status' => in_array($_POST['status'] ?? 'pending', ['pending', 'paid', 'cancelled']) ? $_POST['status'] : 'pending'
        ];

        if (StaffBalance::create($data)) {
            Utils::flash('success', 'Alacak/verecek kaydedildi');
        } else {
            Utils::flash('error', 'Kayıt sırasında hata oluştu');
        }

        redirect('/staff/balances/' . $id);
    }

    public function balances($id)
    {
        Auth::require();
        $staff = Staff::find($id);
        if (!$staff) {
            Utils::flash('error', 'Personel bulunamadı');
            // ===== KOZMOS_STAFF_FIX: fix redirect URL (begin)
redirect(base_url('/staff'));
// ===== KOZMOS_STAFF_FIX: fix redirect URL (end)
        }

        $balances = StaffBalance::getByStaff($id);
        $totalReceivable = StaffBalance::getTotalBalances($id, 'receivable');
        $totalPayable = StaffBalance::getTotalBalances($id, 'payable');
        $netBalance = $totalReceivable - $totalPayable;

        echo View::renderWithLayout('staff/balances', [
            'staff' => $staff,
            'balances' => $balances,
            'totalReceivable' => $totalReceivable,
            'totalPayable' => $totalPayable,
            'netBalance' => $netBalance
        ]);
    }

    public function updateBalance($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Geçersiz istek');
            redirect('/staff/balances/' . $id);
        }

        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['pending', 'paid', 'cancelled'])) {
            Utils::flash('error', 'Geçersiz durum');
            redirect('/staff/balances/' . $id);
        }

        if (StaffBalance::update($id, ['status' => $status])) {
            Utils::flash('success', 'Durum güncellendi');
        } else {
            Utils::flash('error', 'Güncelleme sırasında hata oluştu');
        }

        redirect('/staff/balances/' . $id);
    }
    
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (begin)
    public function bulkStatusUpdate()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $staffIds = $_POST['staff_ids'] ?? [];
        $status = $_POST['status'] ?? '';
        
        if (empty($staffIds) || !is_array($staffIds)) {
            Utils::flash('error', 'Lütfen en az bir personel seçin.');
            redirect(base_url('/staff'));
        }
        
        if (!in_array($status, ['ACTIVE', 'INACTIVE'])) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/staff'));
        }
        
        $db = Database::getInstance();
        $updatedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($staffIds as $staffId) {
                $staff = Staff::find($staffId);
                if (!$staff) continue;
                
                if (Staff::update($staffId, ['status' => $status])) {
                    $updatedCount++;
                    ActivityLogger::log('staff_updated', 'staff', [
                        'staff_id' => $staffId,
                        'status' => $status,
                        'name' => $staff['name'] . ' ' . $staff['surname']
                    ]);
                }
            }
            
            $db->commit();
            
            if ($updatedCount > 0) {
                Utils::flash('success', "{$updatedCount} personelin durumu başarıyla güncellendi.");
            } else {
                Utils::flash('error', 'Hiçbir personel güncellenemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'staff', [
                'action' => 'bulk_status_update',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu güncelleme sırasında hata oluştu.');
        }
        
        redirect(base_url('/staff'));
    }
    
    public function bulkDelete()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $staffIds = $_POST['staff_ids'] ?? [];
        
        if (empty($staffIds) || !is_array($staffIds)) {
            Utils::flash('error', 'Lütfen en az bir personel seçin.');
            redirect(base_url('/staff'));
        }
        
        $db = Database::getInstance();
        $deletedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($staffIds as $staffId) {
                $staff = Staff::find($staffId);
                if (!$staff) continue;
                
                if (Staff::delete($staffId)) {
                    $deletedCount++;
                    ActivityLogger::log('staff_deleted', 'staff', [
                        'staff_id' => $staffId,
                        'name' => $staff['name'] . ' ' . $staff['surname']
                    ]);
                }
            }
            
            $db->commit();
            
            if ($deletedCount > 0) {
                Utils::flash('success', "{$deletedCount} personel başarıyla silindi.");
            } else {
                Utils::flash('error', 'Hiçbir personel silinemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'staff', [
                'action' => 'bulk_delete',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu silme sırasında hata oluştu.');
        }
        
        redirect(base_url('/staff'));
    }
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (end)
}
