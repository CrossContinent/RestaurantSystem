<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/19/17
 * Time: 2:10 PM
 */
class Request
{

    /**
     * @return string Protocol name HTTP/1.1 or HTTP/2
     */
    public function protocol()
    {
        return $_SERVER["SERVER_PROTOCOL"];
    }

    /**
     * @return string Path e.g /users/index
     */
    public function path()
    {
        return $_SERVER["REQUEST_URI"];
    }

    public function body()
    {
        return $_POST;
    }

    /**
     * @return array
     */
    public function query()
    {
        return $_GET;
    }

    /**
     * @return string Http method type GET/POST/PUT/DELETE
     */
    public function method()
    {
        return $_SERVER["REQUEST_METHOD"];
    }

    public function toString()
    {
        return "{$this->method()} {$this->path()}";
    }
}