<?php
/**
 * Test Helper
 * Common utilities for tests
 */

if (!class_exists('RedirectIntercept')) {
    class RedirectIntercept extends Exception
    {
        public string $target;
        public int $status;

        public function __construct(string $target, int $status = 302)
        {
            parent::__construct("Redirect to {$target}", $status);
            $this->target = $target;
            $this->status = $status;
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        throw new RedirectIntercept($url, $status);
    }
}

