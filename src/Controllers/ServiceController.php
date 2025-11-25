<?php

declare(strict_types=1);

/**
 * Service Controller
 * 
 * Handles service-related operations including CRUD operations
 * and service management.
 * 
 * @package App\Controllers
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class ServiceController
{
    /** @var Service $serviceModel */
    private $serviceModel;
    
    /** @var ContractTemplate $templateModel */
    private $templateModel;
    
    /** @var ContractTemplateService $templateService */
    private $templateService;
    
    /**
     * ServiceController constructor
     * Initializes required models
     */
    public function __construct()
    {
        $this->serviceModel = new Service();
        $this->templateModel = new ContractTemplate();
        $this->templateService = new ContractTemplateService();
    }
    
    /**
     * Hizmet listesi
     */
    public function index()
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of service list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        $services = $this->serviceModel->all();
        $stats = $this->serviceModel->getStats();
        $usageStats = $this->serviceModel->getUsageStats();
        
        echo View::renderWithLayout('services/list', [
            'services' => $services,
            'stats' => $stats,
            'usageStats' => $usageStats,
            'flash' => Utils::getFlash(),
        ]);
    }
    
    /**
     * Yeni hizmet formu
     */
    public function create()
    {
        Auth::requireAdmin();
        
        echo View::renderWithLayout('services/form', [
            'service' => null,
            'flash' => Utils::getFlash(),
        ]);
    }
    
    /**
     * Hizmet kaydet
     */
    public function store()
    {
        Auth::requireAdmin();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/services')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/services/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        $validator = new Validator($_POST);
        $validator->required('name', 'Hizmet adı zorunludur')
                 ->min('name', 2, 'Hizmet adı en az 2 karakter olmalıdır')
                 ->max('name', AppConstants::MAX_STRING_LENGTH_MEDIUM, 'Hizmet adı en fazla ' . AppConstants::MAX_STRING_LENGTH_MEDIUM . ' karakter olabilir')
                 ->numeric('duration_min', 'Süre sayısal olmalıdır')
                 ->numeric('default_fee', 'Ücret sayısal olmalıdır');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/services/new'));
        }
        
        $serviceData = [
            'name' => $validator->get('name'),
            'duration_min' => $validator->get('duration_min') ?: null,
            'default_fee' => $validator->get('default_fee') ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $serviceId = $this->serviceModel->create($serviceData);
            
            if ($serviceId) {
                ActivityLogger::log('service_created', 'service', ['service_id' => $serviceId, 'name' => $serviceData['name']]);
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logDataModification('SERVICE_CREATED', Auth::id(), [
                    'service_id' => $serviceId,
                    'service_name' => $serviceData['name'],
                    'duration_min' => $serviceData['duration_min'] ?? null,
                    'default_fee' => $serviceData['default_fee'] ?? null
                ]);
                // ===== ERR-018 FIX: End =====
                
                ControllerHelper::flashSuccessAndRedirect('Hizmet başarıyla oluşturuldu.', '/services');
            } else {
                ControllerHelper::flashErrorAndRedirect('Hizmet oluşturulamadı.', '/services');
            }
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ServiceController::store()', 'Hizmet oluşturulurken bir hata oluştu', '/services');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Hizmet düzenleme formu
     */
    public function edit($id)
    {
        Auth::requireAdmin();
        
        $service = $this->serviceModel->find($id);
        if (!$service) {
            View::notFound('Hizmet bulunamadı');
        }
        
        // Get contract template for this service (for view display)
        $serviceKey = $this->templateService->normalizeServiceName($service['name']);
        $contractTemplate = null;
        if ($serviceKey) {
            $contractTemplate = $this->templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);
        }
        
        echo View::renderWithLayout('services/form', [
            'service' => $service,
            'contractTemplate' => $contractTemplate,
            'flash' => Utils::getFlash(),
        ]);
    }
    
    /**
     * Hizmet güncelle
     */
    public function update($id)
    {
        Auth::requireAdmin();
        
        $service = $this->serviceModel->find($id);
        if (!$service) {
            View::notFound('Hizmet bulunamadı');
        }
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/services')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect("/services/edit/$id")) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        $validator = new Validator($_POST);
        $validator->required('name', 'Hizmet adı zorunludur')
                 ->min('name', 2, 'Hizmet adı en az 2 karakter olmalıdır')
                 ->max('name', AppConstants::MAX_STRING_LENGTH_MEDIUM, 'Hizmet adı en fazla ' . AppConstants::MAX_STRING_LENGTH_MEDIUM . ' karakter olabilir')
                 ->numeric('duration_min', 'Süre sayısal olmalıdır')
                 ->numeric('default_fee', 'Ücret sayısal olmalıdır');
        
        if ($validator->fails()) {
            ControllerHelper::flashErrorAndRedirect($validator->firstError(), "/services/edit/$id");
            return;
        }
        
        $serviceData = [
            'name' => $validator->get('name'),
            'duration_min' => $validator->get('duration_min') ?: null,
            'default_fee' => $validator->get('default_fee') ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $result = $this->serviceModel->update($id, $serviceData);
            
            if ($result) {
                ActivityLogger::log('service_updated', 'service', ['service_id' => $id, 'name' => $serviceData['name']]);
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logDataModification('SERVICE_UPDATED', Auth::id(), [
                    'service_id' => $id,
                    'service_name' => $service['name'] ?? null,
                    'changes' => array_diff_assoc($serviceData, $service)
                ]);
                // ===== ERR-018 FIX: End =====
                
                ControllerHelper::flashSuccessAndRedirect('Hizmet başarıyla güncellendi.', '/services');
            } else {
                ControllerHelper::flashErrorAndRedirect('Hizmet güncellenemedi.', '/services');
            }
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ServiceController::update()', 'Hizmet güncellenirken bir hata oluştu', '/services');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Hizmet sil
     */
    public function delete($id)
    {
        Auth::requireAdmin();
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz hizmet ID.');
            redirect(base_url('/services'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $service = $this->serviceModel->find($id);
        if (!$service) {
            Utils::flash('error', 'Hizmet bulunamadı.');
            redirect(base_url('/services'));
            return;
        }
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/services')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/services')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $result = $this->serviceModel->delete($id);
            
            if ($result) {
                ActivityLogger::log('service_deleted', 'service', ['service_id' => $id, 'name' => $service['name']]);
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logDataModification('SERVICE_DELETED', Auth::id(), [
                    'service_id' => $id,
                    'service_name' => $service['name'] ?? null
                ]);
                // ===== ERR-018 FIX: End =====
                
                ControllerHelper::flashSuccessAndRedirect('Hizmet başarıyla silindi.', '/services');
            } else {
                ControllerHelper::flashErrorAndRedirect('Hizmet silinemedi.', '/services');
            }
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ServiceController::delete()', 'Hizmet silinirken bir hata oluştu', '/services');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Hizmeti aktif/pasif yap
     */
    public function toggleActive($id)
    {
        Auth::require();
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $service = $this->serviceModel->find($id);
            if (!$service) {
                View::notFound('Hizmet bulunamadı');
            }
            
            // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
            if (!ControllerHelper::requirePostOrRedirect('/services')) {
                return;
            }
            
            if (!ControllerHelper::verifyCsrfOrRedirect('/services')) {
                return;
            }
            // ===== ERR-026 FIX: End =====
            
            $result = $this->serviceModel->toggleActive($id);
            
            if ($result) {
                $status = $service['is_active'] ? 'deaktive' : 'aktive';
                ActivityLogger::log('service_toggled', 'service', ['service_id' => $id, 'name' => $service['name'], 'status' => $status]);
                ControllerHelper::flashSuccessAndRedirect("Hizmet $status edildi.", '/services');
            } else {
                ControllerHelper::flashErrorAndRedirect('Hizmet durumu değiştirilemedi.', '/services');
            }
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ServiceController::toggleActive()', 'Hizmet durumu değiştirilirken bir hata oluştu', '/services');
        }
        // ===== ERR-010 FIX: End =====
    }

    /**
     * Hizmet sözleşme şablonu düzenleme formu
     */
    public function editContractTemplate($serviceId)
    {
        Auth::requireAdmin();
        
        $service = $this->serviceModel->find($serviceId);
        if (!$service) {
            View::notFound('Hizmet bulunamadı');
        }
        
        // Service name'den service_key türet
        $serviceKey = $this->templateService->normalizeServiceName($service['name']);
        
        // Template'i bul
        $template = null;
        if ($serviceKey) {
            $template = $this->templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);
        }
        
        // Kullanılabilir placeholder'lar
        $placeholders = [
            '{customer_name}', '{customer_phone}', '{customer_email}',
            '{job_id}', '{job_date}', '{job_time}', '{job_datetime}',
            '{job_address}', '{job_price}', '{job_amount}', '{job_total_amount}',
            '{service_type}', '{service_name}', '{job_description}', '{job_status}'
        ];
        
        echo View::renderWithLayout('services/contract_template_form', [
            'service' => $service,
            'template' => $template,
            'serviceKey' => $serviceKey,
            'placeholders' => $placeholders,
            'flash' => Utils::getFlash(),
        ]);
    }

    /**
     * Hizmet sözleşme şablonu güncelle
     */
    public function updateContractTemplate($serviceId)
    {
        Auth::requireAdmin();
        
        $service = $this->serviceModel->find($serviceId);
        if (!$service) {
            View::notFound('Hizmet bulunamadı');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/services/edit/$serviceId"));
        }
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/services/$serviceId/contract-template/edit"));
        }
        
        // Validation
        $validator = new Validator($_POST);
        $validator->required('name', 'Şablon adı zorunludur')
                 ->required('template_text', 'Sözleşme metni zorunludur')
                 ->min('template_text', 10, 'Sözleşme metni en az 10 karakter olmalıdır');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url("/services/$serviceId/contract-template/edit"));
        }
        
        // Service_key'i türet
        $serviceKey = $this->templateService->normalizeServiceName($service['name']);
        
        // Mevcut template'i bul
        $existingTemplate = null;
        if ($serviceKey) {
            $existingTemplate = $this->templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);
        }
        
        $templateData = [
            'type' => 'cleaning_job',
            'name' => $validator->get('name'),
            'version' => $existingTemplate ? $existingTemplate['version'] : '1.0',
            'description' => sprintf('%s için özel sözleşme şablonu', $service['name']),
            'template_text' => $validator->get('template_text'),
            'service_key' => $serviceKey,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_default' => 0, // Service-specific template'ler default olmamalı
        ];
        
        if ($existingTemplate) {
            // Update
            $result = $this->templateModel->update($existingTemplate['id'], $templateData);
            if ($result) {
                ActivityLogger::log('contract_template_updated', 'contract_template', [
                    'template_id' => $existingTemplate['id'],
                    'service_id' => $serviceId,
                    'service_name' => $service['name']
                ]);
                Utils::flash('success', 'Sözleşme şablonu başarıyla güncellendi.');
            } else {
                Utils::flash('error', 'Sözleşme şablonu güncellenemedi.');
            }
        } else {
            // Create
            $templateId = $this->templateModel->create($templateData);
            if ($templateId) {
                ActivityLogger::log('contract_template_created', 'contract_template', [
                    'template_id' => $templateId,
                    'service_id' => $serviceId,
                    'service_name' => $service['name']
                ]);
                Utils::flash('success', 'Sözleşme şablonu başarıyla oluşturuldu.');
            } else {
                Utils::flash('error', 'Sözleşme şablonu oluşturulamadı.');
            }
        }
        
        redirect(base_url("/services/edit/$serviceId"));
    }
}
