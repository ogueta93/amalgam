<?php
// src/Base/Exception/JsonException.php

namespace App\Base\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DataException extends \RuntimeException implements HttpExceptionInterface
{
    protected $statusCode;
    protected $headers;
    protected $data;

    public function __construct($statusCode, array $data = [], \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->data = $data;

        parent::__construct($data['message'] ?? null, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets array data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set response headers.
     *
     * @param array $headers Response headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
}
