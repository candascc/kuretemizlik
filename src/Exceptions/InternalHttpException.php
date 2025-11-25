<?php

/**
 * Internal HTTP exception used to bubble controller level errors back to the crawler
 * without forcing an exit/redirect.
 */
class InternalHttpException extends RuntimeException
{
    private int $statusCode;
    private ?string $responseBody;

    public function __construct(int $statusCode, string $message = '', ?string $responseBody = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}

