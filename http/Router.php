<?php
Log::setLoggable("RouterDispatcher", false);

/**
 * Inspired by NodeJS Express framework trying to implement
 * similar functionality class
 *
 * User: Ozodrukh
 * Date: 4/19/17
 * Time: 1:21 PM
 */
final class RouterDispatcher
{

    const METHODS = [
        "GET", "POST", "UPDATE", "DELETE", "HEAD",
    ];

    private static function isHttpMethodEquals($original, $cmp)
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
    private $errorCallback;

    /**
     * Called whenever error is occurred while routing
     *
     * @param callable $errorCallback
     */
    public function setErrorCallback($errorCallback)
    {
        $this->errorCallback = $errorCallback;
    }

    /**
     * @param $request Request
     * @param $response Response
     * @param $next callable
     *
     * @return Response
     * @throws HttpRequestException
     */
    public function dispatchHandleRequest($request, $response, $next = null)
    {
        $path = $request->path();

        if (strpos($this->base, $path) == 0) {
            $path = substr($path, strlen($this->base), strlen($path));
        }

        $routes = [];

        /** @var RouteInfo $route */
        foreach ($this->stack as $route) {
            if (!RouterDispatcher::isHttpMethodEquals($request->method(), $route->method)) {
                continue;
            } else if (!$route->matches($path, null)) {
                continue;
            }

            // fix Router path
            if ($route->callback instanceof RouterDispatcher) {
                $route->callback->base = $this->base . $path;
            }

            // Satisfied router found, collecting
            array_push($routes, $route);
        }

        if (count($routes) == 0) {
            throw new HttpRequestException("Route not found for 
                   <{$request->path()}> | {$request->method()}");
        }


        $chain = new Chain($routes, $next);
        try {
            return $chain->proceed($request, $response);
        } catch (Exception $exception) {

            if (!isset($this->errorCallback)) {
                throw $exception;
            }

            // Wrap it with out Exception
            if (!($exception instanceof HttpException)) {
                $exception = new HttpException($exception, 500);
            }

            $response = call_user_func_array($this->errorCallback, array($exception, $request, $response));

            if (!($response instanceof Response)) {
                throw new Exception("Cannot cast to Response class");
            }
            return $response;
        }
    }

    public function route($path, $router)
    {
        array_push($this->stack, new RouteInfo("*", $path, $router));
    }

    /**
     * invoked for any requests passed to this router
     *
     * @param $callback callable
     */
    public function middleware($callback)
    {
        array_push($this->stack, new RouteInfo("*", "*", $callback));
    }

    /**
     * @param $method string
     * @param $path string
     * @param $callback mixed
     *
     * @throws InvalidArgumentException
     */
    public function path($method, $path, $callback)
    {
        if (!in_array($method, RouterDispatcher::METHODS, true)) {
            throw new InvalidArgumentException("No such method={$method}");
        }

        array_push($this->stack, new RouteInfo($method, $path, $callback));
    }
}

class Chain
{

    protected $routes;

    /**
     * @var callable
     */
    protected $next;

    /**
     * Chain constructor.
     *
     * @param $routes array
     * @param $next callable
     */
    public function __construct($routes, $next = null)
    {
        $this->routes = $routes;
        $this->next = $next;
    }

    /**
     * @param $request Request
     * @param $response Response
     * @return Response
     * @throws HttpException
     */
    public function proceed($request, $response)
    {
        $count = count($this->routes);
        Log::write("debug", "RouterDispatcher",
            "----routes({$count})");

        if (count($this->routes) == 0) {
            $next = isset($this->next) ? "next()" : "end();";

            Log::write("debug", "RouterDispatcher",
                "proceeding request({$request->path()}) with {$next}");
        }

        if (count($this->routes) == 0) {
            if (isset($this->next)) {
                $callback = $this->next;
                unset($this->next);
                return call_user_func_array($callback, array($request, $response, array($this, "proceed")));
            } else {
                return $response;
            }
        }

        /** @var RouteInfo $route */
        $route = array_shift($this->routes);

        if ($route == null) {
            Log::write("error", "RouterDispatcher", "Router is null");
            return $response;
        }

        Log::write("debug", "RouterDispatcher",
            "proceeding request({$request->path()}) with {$route->toString()}");

        /** @var $route RouteInfo */
        if ($route->isDispatcher()) {
            Log::write("debug", "RouterDispatcher", "Going deeper {$route->path}");
            $route->callback->dispatchHandleRequest($request, $response, array($this, "proceed"));
        } else {
            call_user_func_array($route->callback, array($request, $response, array($this, "proceed")));
        }
        return $response;
    }
}

class HttpException
{
    /** @var Exception */
    private $exception;
    /**@var int */
    private $status;

    /**
     * HttpException constructor.
     * @param Exception $exception
     * @param int $status
     */
    public function __construct(Exception $exception, $status)
    {
        $this->exception = $exception;
        $this->status = $status;
    }

    /**
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}

class RouteInfo
{
    /** @var string HTTP Method */
    public $method;

    /** @var string HTTP Uri */
    public $path;

    /** @var mixed Callback to handle function */
    public $callback;

    public function isMiddleware()
    {
        return $this->path === "*" && $this->method === "*";
    }

    public function isDispatcher()
    {
        return $this->callback instanceof RouterDispatcher;
    }

    /**
     * RouteInfo constructor.
     * @param $method
     * @param $path
     * @param $callback
     */
    public function __construct($method, $path, $callback)
    {
        $this->method = strtolower($method);

        $this->path = strtolower($path);
        $this->path = str_replace("/", "/", $this->path);
        $this->callback = $callback;
    }

    /**
     * @param $path string
     * @param $matches array
     * @return bool True if matches path string
     */
    public function matches($path, $matches)
    {
        if ($this->path === "*") {
            return true;
        } else {
            return preg_match("/^{$this->path}$/", $path, $matches);
        }
    }

    public function toString()
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