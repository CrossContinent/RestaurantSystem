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
     * Variables that can walk form Chain to Chain,
     * used to communicate with functions sequence
     *
     * @var array
     */
    private $vars;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->headers = static::getAllHeaders();

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
     * Creates or Replaces existing variable
     *
     * @param string $name Variable name
     * @param mixed $value Value
     * @param bool $mutable Whether it allowed to variable be overwritten
     */
    public function setVariable(string $name, $value, bool $mutable = true)
    {
        if (isset($this->vars[$name])) {
            if (!$this->vars[$name]['m']) {
                throw new RuntimeException("cannot override immutable variable");
            }

            $this->vars[$name] = ['m' => $mutable, 'v' => $value];
        }
    }

    /**
     * Returns variable if exists
     *
     * @param string $name Variable name
     * @return mixed|null
     */
    public function variable(string $name)
    {
        if (isset($this->vars[$name])) {
            return $this->vars[$name]['v'];
        } else {
            return null;
        }
    }

    /**
     * @return array Of query parameters from URL,
     * using Global $_GET variable
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

    private final static function getAllHeaders()
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } else if ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } else if ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }
        return $headers;
    }
}