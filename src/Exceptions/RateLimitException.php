<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Exceptions;

final class RateLimitException extends ApiException
{
    public function __construct(
        int $statusCode,
        string $message,
        private readonly ?int $retryAfter = null,
        ?string $errorCode = null,
        ?string $requestId = null,
        mixed $responseBody = null,
        array $headers = []
    ) {
        parent::__construct($statusCode, $message, $errorCode, $requestId, $responseBody, $headers);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
