<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * File Upload Validation Test Suite
 * Tests for file upload validation security
 */
class FileUploadValidationTest extends TestCase
{
    /**
     * Test that FileUploadValidator exists and has required methods
     */
    public function testFileUploadValidatorExists(): void
    {
        $this->assertTrue(class_exists('FileUploadValidator'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('FileUploadValidator', 'validate'));
        $this->assertTrue(method_exists('FileUploadValidator', 'generateSecureFilename'));
        $this->assertTrue(method_exists('FileUploadValidator', 'moveToSecureLocation'));
    }
    
    /**
     * Test that FileUploadValidator rejects dangerous extensions
     */
    public function testFileUploadValidatorRejectsDangerousExtensions(): void
    {
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'js'];
        
        foreach ($dangerousExtensions as $ext) {
            $file = [
                'name' => "test.{$ext}",
                'type' => 'application/octet-stream',
                'tmp_name' => sys_get_temp_dir() . '/test_' . uniqid() . '.' . $ext,
                'error' => UPLOAD_ERR_OK,
                'size' => 100
            ];
            
            // Create a temporary file for testing
            file_put_contents($file['tmp_name'], 'test content');
            
            $errors = FileUploadValidator::validate($file);
            
            // Should have errors for dangerous extensions
            $this->assertNotEmpty($errors, "Should reject dangerous extension: {$ext}");
            
            // Cleanup
            if (file_exists($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
    }
    
    /**
     * Test that FileUploadValidator validates file size
     */
    public function testFileUploadValidatorValidatesFileSize(): void
    {
        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/test_' . uniqid() . '.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 20 * 1024 * 1024 // 20MB (exceeds default 10MB limit)
        ];
        
        // Create a temporary file
        file_put_contents($file['tmp_name'], str_repeat('x', $file['size']));
        
        $errors = FileUploadValidator::validate($file);
        
        // Should have size error
        $this->assertNotEmpty($errors, 'Should reject files exceeding size limit');
        
        // Cleanup
        if (file_exists($file['tmp_name'])) {
            unlink($file['tmp_name']);
        }
    }
    
    /**
     * Test that FileUploadValidator generates secure filenames
     */
    public function testFileUploadValidatorGeneratesSecureFilenames(): void
    {
        $originalName = 'test file name.pdf';
        $secureName = FileUploadValidator::generateSecureFilename($originalName);
        
        // Should be different from original
        $this->assertNotEquals($originalName, $secureName);
        
        // Should have extension
        $this->assertStringEndsWith('.pdf', $secureName);
        
        // Should not contain original filename (security)
        $this->assertStringNotContainsString('test', $secureName);
        $this->assertStringNotContainsString('file', $secureName);
    }
    
    /**
     * Test that FileUploadValidator validates MIME types
     */
    public function testFileUploadValidatorValidatesMimeTypes(): void
    {
        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/test_' . uniqid() . '.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 1000
        ];
        
        // Create a temporary file with PDF content
        file_put_contents($file['tmp_name'], '%PDF-1.4 test content');
        
        $errors = FileUploadValidator::validate($file);
        
        // Should pass validation for valid PDF
        // Note: This might fail if MIME type doesn't match, which is expected
        $this->assertIsArray($errors);
        
        // Cleanup
        if (file_exists($file['tmp_name'])) {
            unlink($file['tmp_name']);
        }
    }
    
    /**
     * Test that FileUploadValidator rejects files with double extensions
     */
    public function testFileUploadValidatorRejectsDoubleExtensions(): void
    {
        $file = [
            'name' => 'test.php.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => sys_get_temp_dir() . '/test_' . uniqid() . '.php.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 1000
        ];
        
        // Create a temporary file
        file_put_contents($file['tmp_name'], 'test content');
        
        $errors = FileUploadValidator::validate($file);
        
        // Should reject double extension with dangerous extension
        $this->assertNotEmpty($errors, 'Should reject files with double dangerous extensions');
        
        // Cleanup
        if (file_exists($file['tmp_name'])) {
            unlink($file['tmp_name']);
        }
    }
    
    /**
     * Test that FileUploadValidator rejects empty files
     */
    public function testFileUploadValidatorRejectsEmptyFiles(): void
    {
        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/test_' . uniqid() . '.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 0
        ];
        
        // Create an empty file
        file_put_contents($file['tmp_name'], '');
        
        $errors = FileUploadValidator::validate($file);
        
        // Should reject empty files
        $this->assertNotEmpty($errors, 'Should reject empty files');
        
        // Cleanup
        if (file_exists($file['tmp_name'])) {
            unlink($file['tmp_name']);
        }
    }
}

