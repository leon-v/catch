<?php

namespace App\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Api extends Exception implements HttpExceptionInterface
{
    public int $statusCode = 500;

    public array $headers = [];

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getHeaders(): array {
        return $this->headers;
    }
}
