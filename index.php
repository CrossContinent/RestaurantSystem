<?php

date_default_timezone_set("Asia/Tashkent");

require_once "logger/Log.php";

require_once "http/Router.php";
require_once "http/Request.php";
require_once "http/Response.php";

require_once "router/UserRouter.php";

$dispatcher = new RouterDispatcher();
$dispatcher->onErrorReturn(function ($error, $req, $res) {
    /** @var $error Exception */
    /** @var $res Response */
    Log::write("debug", "RequestDispatcher", $error);

    $htmlTemplate = "<h1>500 Internal Server Error</h1>";
    $htmlTemplate .= "<strong style='font-size: 18px'>{$error->getMessage()}</strong>";
    $htmlTemplate .= "<pre>";
    foreach ($error->getTrace() as $trace) {
        $line = "";
        foreach ($trace as $key => $value) {
            $line .= "<strong>{$key}</strong>: {$value} ";
        }
        $htmlTemplate .= "{$line}\n";
    }
    $htmlTemplate .= "</pre>";

    $res->status($error->getCode())
        ->setContentType("text/html")
        ->send($htmlTemplate);
});

$dispatcher->middleware(function ($req, $res, $chain) {
    /**
     * @var $req Request
     * @var $res Response
     * @var $chain Chain
     */

    Log::write("debug", "RequestDispatcher", $req->toString());

    $chain->proceed($req, $res);
});

$dispatcher->path("GET", '', function ($req, $res, $chain) {
    /**
     * @var $req Request
     * @var $res Response
     * @var $chain Chain
     */

    throw new Exception("Sample error thrown", 500);

    $res->status(200)->json(array("status" => "ok"));

    $chain->proceed($req, $res);
});

$users = new UserRouter();
$dispatcher->path("GET", 'users', $users->dispatcher());

$dispatcher->middleware(function ($req, $res) {
    /**
     * @var $req Request
     * @var $res Response
     */
    $res->status(404)->setContentType("text/html")->send("404, Not found");
});

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