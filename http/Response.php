<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/20/17
 * Time: 9:55 AM
 */
class Response
{

    /**
     * @var int HTTP status code
     */
    private $status = 0;
    private $body = null;
    private $headers = array();

    /**
     * @param int $status HTTP status code
     * @return Response self Builder pattern
     */
    public function status(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return bool True when body already written, otherwise returns False
     */
    public function hasBody(): bool
    {
        return isset($this->body);
    }

    public function isHeadersSent(): bool
    {
        return headers_sent();
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @return array|string
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * @param $body array Arrays only are accepted
     */
    public function json($body)
    {
        $this->setContentType("application/json");

        if (is_array($body)) {
            $this->body = json_encode($body);
        } else {
            throw new InvalidArgumentException("only Array are is accepted");
        }
    }

    public function send($message)
    {
        if (!isset($this->headers["Content-Type"])) {
            $this->setContentType("text/plain");
        }

        $this->body = $message;
    }

    /**
     * @param $name string Header name
     * @param $value string Header value
     * @return Response self Builder pattern
     */
    public function setHeader($name, $value)
    {
        if (!is_string($name) || !is_string($value)) {
            throw new InvalidArgumentException("Header Name & Value must be string types");
        }
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * sets Content-Type header
     *
     * @param $encodings string Document encoding name
     * @param $contentType string our framework used only for API declaration, therefore JSON by default
     * @return Response self Builder pattern
     */
    public function setContentType($contentType = "application/json", $encodings = "utf-8")
    {
        if (!is_string($contentType) || !is_string($encodings)) {
            throw new InvalidArgumentException("Header Name & Value must be string types");
        }
        $this->headers["Content-Type"] = "{$contentType}; charset=$encodings";
        return $this;
    }

    /**
     * @return array of headers added
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $response = "HTTP {$this->status}";

        if (count($this->headers) > 0) {
            foreach ($this->headers as $name => $value) {
                $response .= "{$name}: {$value}\n";
            }
            $response = "\n";
        }

        if (is_array($this->body)) {
            $response .= json_decode($this->body);
        } else {
            $response .= $this->body;
        }
        return $response;
    }
}