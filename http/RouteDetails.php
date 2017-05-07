<?php

class RouteDetails
{
    /** @var string HTTP Method */
    public $method;

    /** @var string HTTP Uri */
    public $path;

    /** @var mixed Callback to handle function */
    public $callback;

    public function isMiddleware(): bool
    {
        return $this->path === "*" && $this->method === "*";
    }

    public function isDispatcher(): bool
    {
        return $this->callback instanceof Router;
    }

    /**
     * RouteInfo constructor.
     * @param $method
     * @param $path
     * @param $callback
     */
    public function __construct(string $method, string $path, $callback)
    {
        $this->path = $path;
        $this->callback = $callback;
        $this->method = strtolower($method);
    }

    public function __toString(): string
    {
        if ($this->isMiddleware()) {
            return "middleware()";
        } else if ($this->isDispatcher()) {
            return "dispatcher()";
        } else {
            return "{$this->method} - {$this->path}: callback()";
        }
    }
}