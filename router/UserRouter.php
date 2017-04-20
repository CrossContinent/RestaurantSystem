<?php

class UserRouter
{

    /**
     * @return RouterDispatcher
     */
    public function dispatcher()
    {
        $dispatcher = new RouterDispatcher();
        $dispatcher->path("GET", "", array($this, 'index'));
        return $dispatcher;
    }

    public function index($req, $res, $next)
    {
        /**
         * @var $req Request
         * @var $res Response
         */

        $res->send("Hello, Man are on IndexPage on /users");
        call_user_func_array($next, array($req, $res));
    }
}

?>