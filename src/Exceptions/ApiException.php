<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Exceptions;

class ApiException extends MailrifyException
{
    /** @param array<string, string|string[]> $headers */
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly ?string $errorCode = null,
        private readonly ?string $requestId = null,
        private readonly mixed $responseBody = null,
        private readonly array $headers = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }

    /** @return array<string, string|string[]> */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
