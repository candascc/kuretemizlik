<?php
/**
 * Error Sink Interface
 * 
 * ROUND 5 - STAGE 1: Provider-agnostic interface for external error tracking
 * 
 * This interface allows different error tracking providers (Sentry, ELK, CloudWatch, etc.)
 * to be used interchangeably without changing the core error handling logic.
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

interface ErrorSinkInterface
{
    /**
     * Send error/exception data to external monitoring system
     * 
     * @param array $payload Structured error data (exception, context, request info, etc.)
     * @return void
     * @throws Exception If sending fails (should be caught by caller)
     */
    public function send(array $payload): void;
    
    /**
     * Check if this sink is enabled and configured
     * 
     * @return bool
     */
    public function isEnabled(): bool;
}

