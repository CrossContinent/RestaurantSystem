<?php
define("ROUTER_TAG", "RouterDispatcher");

Log::setLoggable(ROUTER_TAG, true);

/**
 * Inspired by NodeJS Express framework trying to implement
 * similar functionality class
 *
 * User: Ozodrukh
 * Date: 4/19/17
 * Time: 1:21 PM
 *
 * @method post(string $path, ...$callbacks)
 * @method get(string $path, ...$callbacks)
 * @method update(string $path, ...$callbacks)
 * @method delete(string $path, ...$callbacks)
 * @method head(string $path, ...$callbacks)
 */
final class RouterDispatcher
{

    const METHODS = [
        "GET", "POST", "UPDATE", "DELETE", "HEAD",
    ];

    private static function isHttpMethodEquals(string $original, string $cmp)
    {
        return $cmp == "*" || strtolower($original) == strtolower($cmp);
    }

    /**
     * @var array Callbacks Map Dictionary<FormMethod, Dictionary<Path, Callback>>
     */
    private $stack = array();

    private $base = '/';

    /**
     * @var callable Listener for error
     */
    private $onErrorCallback;

    /**
     * Called whenever error is occurred while routing
     *
     * @param callable $errorCallback
     */
    public function onErrorReturn(callable $errorCallback)
    {
        $this->onErrorCallback = $errorCallback;
    }

    public function start()
    {
        $request = new Request();
        $response = new Response();

        $routes = $this->dispatchHandleRequest($request, $response);

        if (count($routes) === 0) {
            throw new BadMethodCallException("Route not found for 
                   <{$request->path()}> | {$request->method()}");
        }

        $chain = new Chain($routes);

        try {
            $response = $chain->proceed($request, $response);
        } catch (Exception $exception) {

            if (!isset($this->onErrorCallback)) {
                throw $exception;
            }

            $errorResponse = call_user_func_array($this->onErrorCallback, array($exception, $request, $response));

            if (!empty($errorResponse) && !($errorResponse instanceof Response)) {
                throw new Exception("Cannot cast to Response class");
            }

            $response = $errorResponse;
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $key => $value) {
            header("{$key}: {$value}");
        }

        print($response->body());
    }

    /**
     * Dispatch request to proceed and fill the response object
     *
     * @param $request Request Base request
     * @param $response Response Base response
     *
     * @param $parent Chain Function to proceed in the end
     * @return array Routes
     *
     * @throws Exception
     * @throws HttpRequestException
     */
    public function dispatchHandleRequest(Request $request, Response $response, Chain $parent = null)
    {
        Log::debug(ROUTER_TAG, "Handing Dispatcher for {$this->base}");

        $path = $request->path();

        if (strpos($this->base, $path) == 0) {
            $path = substr($path, strlen($this->base), strlen($path));
        }

        $routes = [];

        Log::debug(ROUTER_TAG, "Request({$request->method()} {$request->path()})");

        /** @var RouteDetails $route */
        foreach ($this->stack as $route) {

            if (Log::isLoggable(ROUTER_TAG))
                Log::debug(ROUTER_TAG, "comparing Route({$route->method} {$route->path})");

            if (!RouterDispatcher::isHttpMethodEquals($request->method(), $route->method)) {
                continue;
            } else if (!$route->matches($path, null)) {
                continue;
            }

            if ($route->isDispatcher()) {
                // fix Router path
                $route->callback->base = $this->base . $path;

                $routes = array_merge($route->callback->dispatchHandleRequest($request, $response), $routes);
                continue;
            } else {
                // Satisfied router found, collecting
                array_push($routes, $route);
            }

        }

        return $routes;
    }

    public function route(string $path, RouterDispatcher $router)
    {
        array_push($this->stack, new RouteDetails("*", $path, $router));
    }

    /**
     * invoked for any requests passed to this router
     *
     * @param $callback callable
     */
    public function middleware($callback)
    {
        array_push($this->stack, new RouteDetails("*", "*", $callback));
    }

    /**
     * Magic Moments of PHP :)
     *
     * @param $name
     * @param $arguments
     * @return RouterDispatcher
     * @throws Exception
     */
    function __call($name, $arguments)
    {
        if (count($arguments) < 2) {
            throw new Exception("Two few exceptions required 2");
        }

        $name = strtoupper($name);

        if (!in_array($name, RouterDispatcher::METHODS, true)) {
            throw new InvalidArgumentException("No such HTTP Method <{$name}>");
        }

        $path = $arguments[0];

        if (!is_string($path)) {
            throw new InvalidArgumentException("Path must be string type");
        }

        $callbacks = $arguments[1];

        if (count($callbacks) == 0) {
            throw new InvalidArgumentException("Callbacks are missing");
        }

        return $this->path($name, $path, $callbacks);
    }

    /**
     * @param $method string
     * @param $path string
     * @param array $callbacks
     * @internal param mixed $callback
     * @return RouterDispatcher
     */
    public function path(string $method, string $path, ...$callbacks)
    {
        if (!in_array(strtoupper($method), RouterDispatcher::METHODS, true)) {
            throw new InvalidArgumentException("No such method={$method}");
        }

        $callbacksCount = count($callbacks);

        Log::debug(ROUTER_TAG,
            "registering {$method} {$path}: {$callbacksCount} callbacks");

        foreach ($callbacks as $callback) {
            array_push($this->stack, new RouteDetails($method, $path, $callback));
        }

        return $this;
    }
}