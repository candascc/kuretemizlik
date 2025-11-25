<?php
/**
 * Basic middleware contract.
 */

interface MiddlewareInterface
{
    /**
     * @param callable $next
     * @return callable
     */
    public function __invoke(callable $next): callable;
}
