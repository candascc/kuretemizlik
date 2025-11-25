<?php
/**
 * Contract Template Service
 * Sözleşme şablonu render ve job contract oluşturma servisi
 */

class ContractTemplateService
{
    private ContractTemplate $templateModel;
    private JobContract $contractModel;
    private Database $db;

    public function __construct()
    {
        $this->templateModel = new ContractTemplate();
        $this->contractModel = new JobContract();
        $this->db = Database::getInstance();
    }

    /**
     * Service name'den service_key türet
     * 
     * NOT: Service name → service_key mapping TEK BİR YERDE burada tanımlıdır.
     * 
     * @param string $serviceName Service adı (örn: "Ev Temizliği")
     * @return string|null Service key (örn: "house_cleaning") veya null
     */
    public function normalizeServiceName(string $serviceName): ?string
    {
        // Service name → service_key mapping (TEK KAYNAK)
        // Türkçe karakterler dahil, tutarlı lowercase için mb_strtolower kullanılacak
        $mapping = [
            'ev temizliği' => 'house_cleaning',
            'ev temizlik' => 'house_cleaning',
            'ofis temizliği' => 'office_cleaning',
            'ofis temizlik' => 'office_cleaning',
            'iş yeri temizliği' => 'office_cleaning',
            'işyeri temizliği' => 'office_cleaning',
            'cam temizliği' => 'window_cleaning',
            'pencere temizliği' => 'window_cleaning',
            'cam temizlik' => 'window_cleaning',
            'pencere temizlik' => 'window_cleaning',
            'inşaat sonrası temizlik' => 'post_construction',
            'inşaat sonrası' => 'post_construction',
            'taşınma sonrası temizlik' => 'post_construction',
            'taşınma sonrası' => 'post_construction',
            'mağaza temizliği' => 'store_cleaning',
            'mağaza temizlik' => 'store_cleaning',
            'site temizliği' => 'site_common_areas',
            'ortak alan temizliği' => 'site_common_areas',
            'site ve ortak alan temizliği' => 'site_common_areas',
            'site yönetimi' => 'management_service',
            'apartman yönetimi' => 'management_service',
            'yönetim hizmeti' => 'management_service',
            'yönetim' => 'management_service',
            // Yeni mapping'ler (STAGE5)
            'balkon temizliği' => 'balcony_cleaning',
            'balkon temizlik' => 'balcony_cleaning',
            'halı yıkama' => 'carpet_cleaning',
            'hali yikama' => 'carpet_cleaning', // Türkçe karakter olmadan da çalışsın
        ];
        
        // Normalize: küçük harfe çevir, trim, Türkçe karakterleri koru (UTF-8)
        // mb_strtolower Türkçe karakterleri (İ, ı, ş, ğ, ü, ö, ç) doğru işler
        $normalized = mb_strtolower(trim($serviceName), 'UTF-8');
        
        // Önce tam eşleşme kontrolü
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }
        
        // Eğer tam eşleşme yoksa, fazladan boşlukları temizle ve tekrar dene
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }
        
        return null;
    }

    /**
     * Belirli bir service_key için temizlik işi şablonu getir
     * 
     * @param string $serviceKey Service key (örn: 'house_cleaning')
     * @return array|null Şablon kaydı veya null
     */
    public function getCleaningJobTemplateForService(string $serviceKey): ?array
    {
        return $this->templateModel->findByTypeAndServiceKey(
            'cleaning_job',
            $serviceKey,
            true // activeOnly
        );
    }

    /**
     * Bir iş için uygun sözleşme şablonunu getir
     * 
     * Seçim sırası:
     * 1. Job'ın service_id'sine göre service-specific template
     * 2. Fallback: Genel default template (service_key IS NULL)
     * 3. Fallback: Herhangi bir aktif cleaning_job template (son çare)
     * 
     * @param array $job Job kaydı (service_id, service_name içermeli)
     * @param array|null $customer Customer kaydı (opsiyonel)
     * @return array|null Şablon kaydı veya null
     */
    public function getTemplateForJob(array $job, ?array $customer = null): ?array
    {
        // 1. Service kontrolü
        if (empty($job['service_id'])) {
            // Service yok, genel template'e düş
            error_log("Job {$job['id']} has no service_id, using default template");
            return $this->getDefaultCleaningJobTemplate();
        }
        
        // 2. Service kaydını getir
        // Eğer job içinde service_name varsa onu kullan, yoksa Service model'den çek
        $serviceName = $job['service_name'] ?? null;
        
        if (!$serviceName) {
            $serviceModel = new Service();
            $service = $serviceModel->find($job['service_id']);
            
            if (!$service) {
                // Service bulunamadı, genel template'e düş
                error_log("Service not found for job {$job['id']}, service_id: {$job['service_id']}, using default template");
                return $this->getDefaultCleaningJobTemplate();
            }
            
            $serviceName = $service['name'];
        }
        
        // 3. Service name'den service_key türet
        $serviceKey = $this->normalizeServiceName($serviceName);
        
        if (!$serviceKey) {
            // Service name mapping'de yok, genel template'e düş
            error_log("Service name '{$serviceName}' not mapped to service_key for job {$job['id']}, using default template");
            return $this->getDefaultCleaningJobTemplate();
        }
        
        // 4. Service-specific template ara
        $template = $this->getCleaningJobTemplateForService($serviceKey);
        
        if ($template) {
            return $template;
        }
        
        // 5. Fallback: Genel default template
        $defaultTemplate = $this->getDefaultCleaningJobTemplate();
        
        if ($defaultTemplate) {
            error_log("Service-specific template not found for service_key '{$serviceKey}' (job {$job['id']}), using default template");
            return $defaultTemplate;
        }
        
        // 6. Son çare: Herhangi bir aktif cleaning_job template
        $anyTemplate = $this->templateModel->findByTypeAndServiceKey('cleaning_job', null, true);
        
        if ($anyTemplate) {
            error_log("CRITICAL: No default template found, using any active cleaning_job template for job {$job['id']}");
            if (class_exists('Logger')) {
                Logger::warning('No default contract template, using fallback', [
                    'job_id' => $job['id'],
                    'service_id' => $job['service_id'],
                    'service_name' => $service['name'],
                    'service_key' => $serviceKey,
                ]);
            }
            return $anyTemplate;
        }
        
        // 7. Hiçbiri yok - kritik durum
        error_log("CRITICAL: No contract template available for job {$job['id']} (service: {$service['name']}, key: {$serviceKey})");
        if (class_exists('Logger')) {
            Logger::error('No contract template available', [
                'job_id' => $job['id'],
                'service_id' => $job['service_id'],
                'service_name' => $service['name'],
                'service_key' => $serviceKey,
            ]);
        }
        
        return null;
    }

    /**
     * Varsayılan temizlik işi şablonunu getir
     *
     * @return array|null Şablon kaydı veya null
     * @throws Exception Şablon bulunamazsa exception fırlatır (proje pattern'ine göre)
     */
    public function getDefaultCleaningJobTemplate(): ?array
    {
        $template = $this->templateModel->getDefault('cleaning_job');
        
        if (!$template) {
            // Proje pattern'ine göre: null döndürüyoruz, controller seviyesinde exception fırlatılabilir
            // Alternatif olarak: throw new Exception('Varsayılan temizlik işi sözleşme şablonu bulunamadı. Lütfen yönetici ile iletişime geçin.');
            return null;
        }

        return $template;
    }

    /**
     * Temizlik işi sözleşme metnini render et (placeholder'ları doldur)
     *
     * @param array $template ContractTemplate kaydı
     * @param array $job Job kaydı (JOIN ile customer, service, address bilgileri dahil)
     * @param array $customer Customer kaydı (opsiyonel, job içinde de olabilir)
     * @return string Render edilmiş sözleşme metni
     * @throws Exception Şablon metni boşsa exception fırlatır
     */
    public function renderCleaningJobContractText(array $template, array $job, ?array $customer = null): string
    {
        $templateText = $template['template_text'] ?? '';
        
        if (empty($templateText)) {
            throw new Exception('Şablon metni boş olamaz.');
        }

        // Customer bilgilerini hazırla
        if (!$customer) {
            $customer = [
                'name' => $job['customer_name'] ?? 'Müşteri',
                'phone' => $job['customer_phone'] ?? '',
                'email' => $job['customer_email'] ?? '',
            ];
        }

        // Job tarih bilgileri
        $jobDate = '';
        if (!empty($job['start_at'])) {
            $jobDate = Utils::formatDateTime($job['start_at'], 'd.m.Y');
        }

        $jobTime = '';
        if (!empty($job['start_at']) && !empty($job['end_at'])) {
            $startTime = Utils::formatDateTime($job['start_at'], 'H:i');
            $endTime = Utils::formatDateTime($job['end_at'], 'H:i');
            $jobTime = "{$startTime} - {$endTime}";
        }

        // Adres bilgileri
        $jobAddress = '';
        if (!empty($job['address_line'])) {
            $jobAddress = trim($job['address_line']);
            if (!empty($job['address_city'])) {
                $jobAddress .= ', ' . trim($job['address_city']);
            }
            if (!empty($job['address_label'])) {
                $jobAddress = trim($job['address_label']) . ': ' . $jobAddress;
            }
        }

        // Ücret bilgisi
        $jobPrice = Utils::formatMoney($job['total_amount'] ?? 0);

        // Hizmet tipi
        $serviceType = $job['service_name'] ?? 'Temizlik Hizmeti';

        // Placeholder mapping
        $placeholders = [
            '{customer_name}' => htmlspecialchars($customer['name'] ?? 'Müşteri'),
            '{customer_phone}' => htmlspecialchars($customer['phone'] ?? ''),
            '{customer_email}' => htmlspecialchars($customer['email'] ?? ''),
            '{job_id}' => (string)($job['id'] ?? ''),
            '{job_date}' => $jobDate,
            '{job_time}' => $jobTime,
            '{job_datetime}' => !empty($job['start_at']) ? Utils::formatDateTime($job['start_at'], 'd.m.Y H:i') : '',
            '{job_address}' => htmlspecialchars($jobAddress),
            '{job_price}' => $jobPrice,
            '{job_amount}' => $jobPrice, // Alias
            '{job_total_amount}' => $jobPrice, // Alias
            '{service_type}' => htmlspecialchars($serviceType),
            '{service_name}' => htmlspecialchars($serviceType),
            '{job_description}' => htmlspecialchars($job['note'] ?? ''),
            '{job_status}' => htmlspecialchars($job['status'] ?? ''),
        ];

        // Basit string replace ile placeholder'ları doldur
        // TODO: İleride daha gelişmiş placeholder engine'e evrilebilir (Twig, Mustache vb.)
        $renderedText = $templateText;
        foreach ($placeholders as $placeholder => $value) {
            $renderedText = str_replace($placeholder, $value, $renderedText);
        }

        return $renderedText;
    }

    /**
     * Bir iş için sözleşme oluştur (veya mevcut olanı döndür)
     *
     * Bu metot aşağıdakileri tek seferde yapar:
     * - Var olan JobContract var mı kontrol eder
     * - Yoksa varsayılan şablonu çeker ve metni render eder
     * - JobContract kaydını oluşturur (contract_hash ile)
     *
     * @param int|array $job Job ID veya Job kaydı
     * @param array|null $customer Customer kaydı (opsiyonel, job içinden alınabilir)
     * @return array JobContract kaydı
     * @throws Exception Job bulunamazsa veya şablon yoksa exception fırlatır
     */
    public function createJobContractForJob($job, ?array $customer = null): array
    {
        // Job kaydını getir
        $jobModel = new Job();
        if (is_int($job)) {
            $jobRecord = $jobModel->find($job);
        } else {
            $jobRecord = $job;
        }

        if (!$jobRecord) {
            throw new Exception('İş kaydı bulunamadı.');
        }

        // Mevcut sözleşme var mı kontrol et
        $existingContract = $this->contractModel->findByJobId($jobRecord['id']);
        if ($existingContract) {
            return $existingContract;
        }

        // Customer bilgilerini hazırla
        if (!$customer) {
            $customerModel = new Customer();
            $customer = $customerModel->find($jobRecord['customer_id']);
            if (!$customer) {
                throw new Exception('Müşteri kaydı bulunamadı.');
            }
        }

        // İş için uygun şablonu getir (service-specific veya genel)
        $template = $this->getTemplateForJob($jobRecord, $customer);
        if (!$template) {
            throw new Exception('Temizlik işi sözleşme şablonu bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        // Sözleşme metnini render et
        $contractText = $this->renderCleaningJobContractText($template, $jobRecord, $customer);

        // Contract hash hesapla (değişiklik takibi için)
        $contractHash = hash('sha256', $contractText);

        // JobContract kaydını oluştur
        $contractId = $this->contractModel->create([
            'job_id' => $jobRecord['id'],
            'template_id' => $template['id'],
            'status' => 'PENDING',
            'approval_method' => 'SMS_OTP',
            'contract_text' => $contractText,
            'contract_hash' => $contractHash,
            'expires_at' => $jobRecord['start_at'], // İş başlangıç tarihine kadar geçerli (opsiyonel olarak +30 gün eklenebilir)
        ]);

        if (!$contractId) {
            throw new Exception('Sözleşme oluşturulurken hata oluştu.');
        }

        // Oluşturulan sözleşmeyi döndür
        $contract = $this->contractModel->find($contractId);
        if (!$contract) {
            throw new Exception('Oluşturulan sözleşme bulunamadı.');
        }

        return $contract;
    }
}

