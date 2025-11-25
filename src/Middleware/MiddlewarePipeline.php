<?php
/**
 * Simple middleware pipeline runner.
 */

class MiddlewarePipeline
{
    /** @var callable */
    private $target;

    /** @var array<int, callable> */
    private array $middlewares = [];

    public function __construct(callable $target)
    {
        $this->target = $target;
    }

    public function pipe(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function resolve(): callable
    {
        $handler = $this->target;

        while ($middleware = array_pop($this->middlewares)) {
            $handler = $middleware($handler);
        }

        return $handler;
    }
}
