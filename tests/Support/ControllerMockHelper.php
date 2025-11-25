<?php
/**
 * Controller Mock Helper
 * 
 * Provides utilities for mocking controllers, requests, and responses
 * in functional tests
 */

namespace Tests\Support;

class ControllerMockHelper
{
    /**
     * Create a mock request
     */
    public static function createRequest(array $data = [], string $method = 'POST'): array
    {
        return [
            'method' => $method,
            'data' => $data,
            'headers' => $data['headers'] ?? [],
            'server' => $data['server'] ?? [],
        ];
    }
    
    /**
     * Setup request globals
     */
    public static function setupRequestGlobals(array $request): void
    {
        $_SERVER['REQUEST_METHOD'] = $request['method'];
        
        if ($request['method'] === 'POST') {
            $_POST = $request['data'] ?? [];
            $_GET = [];
        } else {
            $_GET = $request['data'] ?? [];
            $_POST = [];
        }
        
        foreach ($request['server'] ?? [] as $key => $value) {
            $_SERVER[$key] = $value;
        }
        
        foreach ($request['headers'] ?? [] as $key => $value) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$headerKey] = $value;
        }
    }
    
    /**
     * Cleanup request globals
     */
    public static function cleanupRequestGlobals(): void
    {
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * Setup session for test
     */
    public static function setupSession(array $data = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        session_start();
        $_SESSION = array_merge($_SESSION, $data);
    }
    
    /**
     * Cleanup session
     */
    public static function cleanupSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
    }
    
    /**
     * Mock controller method call
     */
    public static function callControllerMethod(
        string $controllerClass,
        string $method,
        array $request = [],
        array $session = []
    ): mixed {
        self::setupRequestGlobals($request);
        self::setupSession($session);
        
        try {
            $controller = new $controllerClass();
            
            // Capture output
            ob_start();
            $result = $controller->$method();
            $output = ob_get_clean();
            
            return [
                'result' => $result,
                'output' => $output,
                'session' => $_SESSION,
            ];
        } finally {
            self::cleanupRequestGlobals();
            self::cleanupSession();
        }
    }
    
    /**
     * Mock notification service
     */
    public static function mockNotificationService($controller): void
    {
        // Use reflection to inject mock if needed
        if (method_exists($controller, 'setNotificationService')) {
            $mock = new class {
                public function send($to, $subject, $body) { return true; }
                public function sendSMS($to, $message) { return true; }
            };
            $controller->setNotificationService($mock);
        }
    }
}




