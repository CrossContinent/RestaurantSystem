<?php

define('DEBUG_OUTPUT', 1);
define('AUTH_KEY', '#!F#@!!You|||_@JORJ@');

error_reporting(1);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Tashkent");

require "vendor/autoload.php";

require_once "logger/Log.php";

require_once "http/Request.php";
require_once "http/Response.php";

require_once "http/Chain.php";
require_once "http/Router.php";
require_once "http/RouteDetails.php";
require_once "http/RouteMatcher.php";

require_once "middleware/permissions/Role.php";
require_once "middleware/permissions/Roles.php";
require_once "middleware/permissions/RoleMiddleware.php";

require_once "router/BaseRouter.php";
require_once "router/ModelRouter.php";
require_once "router/UserRouter.php";
require_once "router/ProductRouter.php";
require_once "router/CategoryRouter.php";
require_once "router/AuthenticationException.php";

require_once "database/PersistentModel.php";
require_once "database/DataSource.php";

require_once "domain/Account.php";
require_once "domain/Product.php";
require_once "domain/Category.php";

require_once "libs/jwt/JWT.php";

$dispatcher = new Router();
$dispatcher->onErrorReturn(function (Exception $error, Request $req, Response $res) {
    /** @var $res Response */
    Log::debug("RequestDispatcher", $error);

    if (DEBUG_OUTPUT) {
        $htmlTemplate = "<h1>{$error->getCode()} {$error->getMessage()}</h1>";
        $htmlTemplate .= "<strong style='font-size: 18px'>{$error->getMessage()}</strong>";
        $htmlTemplate .= "<pre>";
        foreach ($error->getTrace() as $trace) {
            $line = "";
            foreach ($trace as $key => $value) {
                $line .= "<strong>{$key}</strong>: " . var_export($value, true);
            }
            $htmlTemplate .= "{$line}\n";
        }
        $htmlTemplate .= "</pre>";

        $res->status($error->getCode())
            ->setContentType("text/html")
            ->send($htmlTemplate);
    } else {
        $res->status($error->getCode())->json([
            'status' => 'failed',
            'message' => $error->getMessage()
        ]);
    }
});

$dispatcher->middleware(function (Request $req, Response $res, Chain $chain) {
    Log::debug("RequestDispatcher",
        "{$req->method()} {$req->path()} \n\t" . json_encode($req->body()));

    $res = $chain->proceed($req, $res);

    $response = "{$req->protocol()} {$res->getStatusCode()}";
    foreach ($res->getHeaders() as $key => $value) {
        $response .= "\n{$key}: {$value}";
    }
    $response .= "\n{$res->body()}";

    Log::debug("ResponseDispatcher", $response);
});

$dispatcher->path("GET", '/', function (Request $req, Response $res, Chain $chain) {
    /**
     * @var $req Request
     * @var $res Response
     * @var $chain Chain
     */

    $res->status(200)->json(array("status" => "ok"));

    $chain->proceed($req, $res);
});

$routers = [
    "/users" => new UserRouter(),
    "/products" => new ProductRouter(),
    "/categories" => new CategoryRouter(),
];

foreach ($routers as $path => $router) {
    $dispatcher->route($path, $router->dispatcher());
}

$dispatcher->options('*', function (Request $req, Response $res, Chain $chain) {
    // Add CORS support
    $res->setHeader("Access-Control-Allow-Origin", "*");
    $res->setHeader("Access-Control-Allow-Headers", "Origin, Authorization, X-Requested-With, Content-Type, Access-Control-Allow-Origin");
    $res->setHeader("Access-Control-Allow-Methods", "PUT, GET, POST, DELETE, OPTIONS");

    $res->send("OK");

    $chain->proceed($req, $res);
});

$dispatcher->middleware(function (Request $req, Response $res, Chain $chain) {
    if (!$res->hasBody()) {
        $res->status(404)->setContentType("text/html")->send("404, Not found");
    }

    $chain->proceed($req, $res);
});

$dispatcher->middleware(function (Request $req, Response $res) {
    Log::debug("ResponseDispatcher", "Ended");
});

$dispatcher->start();

?>