<?php

class Chain
{
    /** @var array */
    private $routes;

    /**
     * Chain constructor.
     *
     * @param $routes array
     * @param $parent Chain
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return bool True chain can proceed to the next
     */
    public function hasNext()
    {
        return isset($this->parent) || count($this->routes) > 0;
    }

    /**
     * @param $request Request
     * @param $response Response
     * @return Response
     * @throws HttpException
     */
    public function proceed(Request $request, Response $response): Response
    {
        $count = count($this->routes);
        Log::debug(Router::TAG, "----routes({$count})");

        if (count($this->routes) == 0) {
            $next = isset($this->parent) ? "next()" : "end();";

            Log::debug(Router::TAG, "proceeding request({$request->path()}) with {$next}");
        }

        if (count($this->routes) == 0) {
            return $response;
        }

        /** @var RouteDetails $route */
        $route = array_shift($this->routes);

        Log::debug(Router::TAG, "proceeding request({$request->path()}) with {$route}");

        call_user_func_array($route->callback, array($request, $response, $this));
        return $response;
    }

    function __toString()
    {
        return implode(' -> ', $this->routes);
    }
}