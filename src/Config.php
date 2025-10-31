<?php

declare(strict_types=1);

namespace Mailrify\Sdk;

use Mailrify\Sdk\Exceptions\ValidationException;

final class Config
{
    private string $apiKey;

    private string $baseUrl;

    private float $timeout;

    private int $maxRetries;

    private bool $debug;

    private ?string $userAgent;

    /**
     * @param array{apiKey?: string, baseUrl?: string, timeout?: float|int, maxRetries?: int, debug?: bool, userAgent?: string|null} $options
     */
    public static function fromArray(array $options): self
    {
        $apiKey = $options['apiKey'] ?? getenv('MAILRIFY_API_KEY') ?: '';
        $baseUrl = $options['baseUrl'] ?? getenv('MAILRIFY_BASE_URL') ?: 'https://app.mailrify.com/api';
        $timeout = isset($options['timeout']) ? (float) $options['timeout'] : 10.0;
        $maxRetries = isset($options['maxRetries']) ? (int) $options['maxRetries'] : 2;
        $debug = isset($options['debug']) ? (bool) $options['debug'] : false;
        $userAgent = $options['userAgent'] ?? null;

        return new self($apiKey, $baseUrl, $timeout, $maxRetries, $debug, $userAgent);
    }

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://app.mailrify.com/api',
        float $timeout = 10.0,
        int $maxRetries = 2,
        bool $debug = false,
        ?string $userAgent = null
    ) {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new ValidationException('API key must be provided.');
        }

        $baseUrl = rtrim($baseUrl, '/');
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Base URL must be a valid URL.');
        }

        if ($timeout <= 0.0) {
            throw new ValidationException('Timeout must be a positive number of seconds.');
        }

        if ($maxRetries < 0) {
            throw new ValidationException('Max retries must be zero or greater.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->debug = $debug;
        $this->userAgent = $userAgent;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
