<?php
/**
 * Authentication Exception
 */

class AuthenticationException extends Exception
{
    public function __construct(string $message = "Authentication required", int $code = 401, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
