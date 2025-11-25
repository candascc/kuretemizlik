<?php
/**
 * ErrorDetector Unit Tests
 */

require_once __DIR__ . '/../../src/Services/ErrorDetector.php';

class ErrorDetectorTest extends PHPUnit\Framework\TestCase
{
    public function testDetectErrorWith500Status(): void
    {
        $detector = new ErrorDetector();
        $error = $detector->detectError('', 500);
        $this->assertNotNull($error);
        $this->assertStringContainsString('500', $error);
    }
    
    public function testDetectErrorWith403Status(): void
    {
        $detector = new ErrorDetector();
        $error = $detector->detectError('<title>403 Forbidden</title>', 403);
        $this->assertNotNull($error);
    }
    
    public function testDetectErrorWithErrorPattern(): void
    {
        $detector = new ErrorDetector();
        $error = $detector->detectError('Sayfa yüklenirken bir hata oluştu', 200);
        $this->assertNotNull($error);
    }
    
    public function testNoErrorForValidPage(): void
    {
        $detector = new ErrorDetector();
        $error = $detector->detectError('<html><body>Valid page content</body></html>', 200);
        $this->assertNull($error);
    }
}

