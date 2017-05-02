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
        return $this->callback instanceof RouterDispatcher;
    }

    /**
     * RouteInfo constructor.
     * @param $method
     * @param $path
     * @param $callback
     */
    public function __construct(string $method, string $path, $callback)
    {
        $this->path = strtolower($path);
        $this->path = str_replace("/", "/", $this->path);
        $this->method = strtolower($method);
        $this->callback = $callback;
    }

    /**
     * @param $path string
     * @param $matches array
     * @return bool True if matches path string
     */
    public function matches(string $path, ?array $matches): bool
    {
        if ($this->path === "*") {
            return true;
        } else {
            $result = preg_match("/^\/?{$this->path}\/?$/", $path, $matches);

            if (preg_last_error() !== PREG_NO_ERROR) {
                Log::error("RouterDispatcher", "Error parsing: " . preg_last_error(), null);
            }

            return $result;
        }
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