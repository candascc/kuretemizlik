<?php
/** @var array $contract */
/** @var array $job */
/** @var array $customer */
/** @var array $service */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? __('contracts.admin.print.title')) ?></title>
    <style>
        /* Print-friendly CSS */
        @media print {
            body { 
                margin: 0; 
                padding: 20px; 
                font-size: 12pt;
            }
            .no-print { 
                display: none !important; 
            }
            .print-button { 
                display: none !important; 
            }
            @page {
                margin: 2cm;
            }
        }
        @media screen {
            body {
                max-width: 210mm;
                margin: 0 auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .print-container {
                background: white;
                padding: 40px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24pt;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 11pt;
            color: #666;
        }
        .contract-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            margin: 30px 0;
        }
        .contract-text {
            margin: 30px 0;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .contract-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10pt;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .footer-item {
            margin: 5px 0;
        }
        .footer-label {
            font-weight: bold;
            display: inline-block;
            min-width: 120px;
        }
        .no-print {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-print {
            background: #2563eb;
            color: white;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-back {
            background: #6b7280;
            color: white;
        }
        .btn-back:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header: Şirket Bilgileri -->
        <div class="header">
            <h1>Küre Temizlik</h1>
            <p>Temizlik Hizmetleri</p>
        </div>
        
        <!-- Sözleşme Başlığı -->
        <div class="contract-title">
            <?= __('contracts.admin.print.contract_title', [
                'service' => htmlspecialchars($service['name'] ?? 'Temizlik')
            ]) ?>
        </div>
        
        <!-- Contract Text -->
        <div class="contract-text">
            <?= nl2br(htmlspecialchars($contract['contract_text'] ?? __('contracts.admin.print.no_text'))) ?>
        </div>
        
        <!-- Footer: Referans Bilgileri -->
        <div class="contract-footer">
            <div class="footer-grid">
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.job_ref') ?>:</span>
                    <?= htmlspecialchars($job['id'] ?? '-') ?>
                </div>
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.contract_ref') ?>:</span>
                    <?= htmlspecialchars($contract['id'] ?? '-') ?>
                </div>
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.job_date') ?>:</span>
                    <?= $job['start_at'] ? Utils::formatDateTime($job['start_at'], 'd.m.Y') : '-' ?>
                </div>
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.customer') ?>:</span>
                    <?= htmlspecialchars($customer['name'] ?? '-') ?>
                </div>
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.approved_at') ?>:</span>
                    <?= $contract['approved_at'] 
                        ? Utils::formatDateTime($contract['approved_at'], 'd.m.Y H:i')
                        : __('contracts.admin.print.not_approved') ?>
                </div>
                <div class="footer-item">
                    <span class="footer-label"><?= __('contracts.admin.print.created_at') ?>:</span>
                    <?= $contract['created_at'] 
                        ? Utils::formatDateTime($contract['created_at'], 'd.m.Y H:i')
                        : '-' ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Print Button (no-print class ile) -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print mr-2"></i>
            <?= __('contracts.admin.print.print_button') ?>
        </button>
        <a href="<?= base_url('/contracts') ?>" class="btn btn-back">
            <i class="fas fa-arrow-left mr-2"></i>
            <?= __('contracts.admin.print.back_button') ?>
        </a>
        <a href="<?= base_url('/jobs/manage/' . ($job['id'] ?? '')) ?>" class="btn btn-back">
            <i class="fas fa-briefcase mr-2"></i>
            <?= __('contracts.admin.print.job_detail') ?>
        </a>
    </div>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

