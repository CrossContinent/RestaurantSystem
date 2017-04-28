<?php

abstract class BaseRouter
{
    private const PREFIX_CALL_LENGTH = 4;

    private static function getCallingMethod($name)
    {
        if (strlen($name) > BaseRouter::PREFIX_CALL_LENGTH &&
            "call" === substr($name, 0, BaseRouter::PREFIX_CALL_LENGTH)
        ) {
            return $name = strtolower(substr($name, BaseRouter::PREFIX_CALL_LENGTH, 1))
                . substr($name, BaseRouter::PREFIX_CALL_LENGTH + 1);
        }

        return null;
    }

    private $dispatcher;

    /**
     * BaseRouter constructor.
     */
    public function __construct()
    {

    }

    function __call($name, $arguments)
    {
        $callingMethod = self::getCallingMethod($name);
        if (method_exists($this, $callingMethod)) {
            return [$this, $callingMethod];
        }

        throw new BadMethodCallException("Method not found {$callingMethod}", 500);
    }

    public final function dispatcher(): RouterDispatcher
    {
        $this->dispatcher = new RouterDispatcher();
        $this->didRouterCreated($this->dispatcher);
        return $this->dispatcher;
    }

    /**
     * Dispatches Router create event
     *
     * Register all routes in {@code $router} that will later
     * registered as sub routing in your Application
     *
     * @param RouterDispatcher $router Router
     */
    public abstract function didRouterCreated(RouterDispatcher $router): void;
}