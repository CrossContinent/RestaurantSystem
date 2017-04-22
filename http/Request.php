<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/19/17
 * Time: 2:10 PM
 */
class Request
{
    private const BODY_STREAM = "php://input";

    /** @var array */
    private $headers;

    /** @var array */
    private $body;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->headers = getallheaders();

        // Fetching HTTP body or Post on failure
        $body = file_get_contents(Request::BODY_STREAM);
        if (!$body) {
            $this->body = $_POST;
        } else {
            $this->body = json_decode($body, true);
        }
    }

    /**
     * @return string Protocol name HTTP/1.1 or HTTP/2
     */
    public function protocol(): string
    {
        return $_SERVER["SERVER_PROTOCOL"];
    }

    /**
     * Gives Header Value
     *
     * @param string $name Header name
     * @return string|null Values associated with given Header name
     */
    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @return string Path e.g /users/index
     */
    public function path(): string
    {
        return $_SERVER["REQUEST_URI"];
    }

    public function body(): array
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function query(): array
    {
        return $_GET;
    }

    /**
     * @return string Http method type GET/POST/PUT/DELETE
     */
    public function method(): string
    {
        return $_SERVER["REQUEST_METHOD"];
    }

    function __toString()
    {
        $response = "\n{$this->method()} {$this->path()}";
        foreach ($this->headers as $key => $value) {
            $response .= "\n{$key}: {$value}";
        }
        $response .= "\n\n" . json_encode($this->body);
        return $response;
    }
}