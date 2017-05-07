<?php

abstract class BaseRouter
{
    private const PREFIX_CALL_LENGTH = 4;

    /**
     * Removes trailing `call` prefix in method name:
     * callSignIn -> signIn
     * callCreateUser -> createUser
     *
     * @param $name string Calling method name with `call` prefix
     * @return null|string method name without `call` prefix
     */
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

    /**
     * Every method that starts with `call` would return method reference
     * to calling method
     *
     * @param $name string Method name
     * @param $arguments mixed no-op
     * @return array Method reference for reflection call
     */
    function __call($name, $arguments)
    {
        $callingMethod = self::getCallingMethod($name);
        if (method_exists($this, $callingMethod)) {
            return [$this, $callingMethod];
        }

        throw new BadMethodCallException("Method not found {$callingMethod}", 500);
    }

    /**
     * Creates dispatcher to install it to {@link RouterDispatcher#route}
     *
     * @return Router
     */
    public final function dispatcher(): Router
    {
        $this->dispatcher = new Router();
        $this->didRouterCreated($this->dispatcher);
        return $this->dispatcher;
    }

    /**
     * Dispatches Router create event
     *
     * Register all routes in {@code $router} that will later
     * registered as sub routing in your Application
     *
     * @param Router $router Router
     */
    public abstract function didRouterCreated(Router $router): void;
}