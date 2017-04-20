<?php

date_default_timezone_set("Asia/Tashkent");

require_once "logger/Log.php";

require_once "http/Router.php";
require_once "http/Request.php";
require_once "http/Response.php";

require_once "router/UserRouter.php";

$dispatcher = new RouterDispatcher();
$dispatcher->middleware(function ($req, $res, $next) {
    /** @var Request $req */

    Log::write("debug", "RequestDispatcher", $req->toString());

    call_user_func_array($next, array($req, $res));
});

$dispatcher->path("GET", '', function ($req, $res, $next) {
    /**
     * @var $req Request
     * @var $res Response
     */

    $res->status(200)->json(array("status" => "ok"));
    call_user_func_array($next, array($req, $res));
});

$users = new UserRouter($dispatcher);
$dispatcher->path("GET", 'users', $users->dispatcher());

$dispatcher->middleware(function ($req, $res) {
    /**
     * @var $req Request
     * @var $res Response
     */
    Log::write("debug", "ResponseDispatcher", "{$req->protocol()} {$res->getStatusCode()}");
    foreach ($res->getHeaders() as $key => $value) {
        Log::write("debug", "ResponseDispatcher", "{$key}: {$value}");
    }
    Log::write("debug", "ResponseDispatcher", $res->body());
});

$request = new Request();
$response = new Response();

/**
 * Updated response
 * @var $response Response
 */
$response = $dispatcher->dispatchHandleRequest($request, $response);

foreach ($response->getHeaders() as $key => $value) {
    header("{$key}: {$value}");
}

print($response->body());

?>