<?php

/**
 * Legal Controller
 * Handles legal pages (privacy policy, terms of use, status)
 * ROUND 31: Created for legal pages hardening
 */
class LegalController
{
    /**
     * Privacy Policy page
     */
    public function privacyPolicy()
    {
        echo View::renderWithLayout('legal/privacy-policy', [
            'title' => 'Gizlilik Politikası',
            'flash' => Utils::getFlash(),
        ]);
    }

    /**
     * Terms of Use page
     */
    public function termsOfUse()
    {
        echo View::renderWithLayout('legal/terms-of-use', [
            'title' => 'Kullanım Şartları',
            'flash' => Utils::getFlash(),
        ]);
    }

    /**
     * System Status page
     * ROUND 31: Simple status page (can be extended with monitoring integration later)
     */
    public function status()
    {
        $status = 'operational';
        $message = 'Sistem çalışıyor';
        
        // ROUND 31: Optional - check system health if SystemHealth class exists
        try {
            if (class_exists('SystemHealth')) {
                $health = SystemHealth::quick();
                if (isset($health['status'])) {
                    $status = $health['status'];
                    $message = $health['status'] === 'healthy' ? 'Sistem çalışıyor' : 'Sistemde sorunlar var';
                }
            }
        } catch (Throwable $e) {
            // Ignore health check errors, use default status
            error_log("LegalController::status() - SystemHealth check error: " . $e->getMessage());
        }

        echo View::renderWithLayout('legal/status', [
            'title' => 'Sistem Durumu',
            'status' => $status,
            'message' => $message,
            'flash' => Utils::getFlash(),
        ]);
    }
}

