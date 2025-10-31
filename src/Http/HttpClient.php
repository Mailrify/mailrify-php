<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Http;

use Mailrify\Sdk\Config;
use Mailrify\Sdk\Exceptions\ApiException;
use Mailrify\Sdk\Exceptions\AuthException;
use Mailrify\Sdk\Exceptions\MailrifyException;
use Mailrify\Sdk\Exceptions\NetworkException;
use Mailrify\Sdk\Exceptions\RateLimitException;
use Mailrify\Sdk\Utils\Arr;
use Mailrify\Sdk\Utils\Json;
use Throwable;

final class HttpClient
{
    private const DEFAULT_USER_AGENT = 'mailrify-php/0.1.0';

    private readonly TransportInterface $transport;

    public function __construct(private readonly Config $config, ?TransportInterface $transport = null)
    {
        $this->transport = $transport ?? HttpClientFactory::createTransport($config);
    }

    /**
     * @param array{query?: array<string, mixed>, json?: array<string, mixed>|list<mixed>|null, headers?: array<string, string>} $options
     */
    public function request(string $method, string $path, array $options = []): mixed
    {
        $method = strtoupper($method);
        $query = Arr::flattenQuery($options['query'] ?? []);
        $providedHeaders = [];
        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $headerName => $headerValue) {
                if (is_string($headerName) && is_string($headerValue)) {
                    $providedHeaders[$headerName] = $headerValue;
                }
            }
        }
        $headers = $providedHeaders;
        $json = $options['json'] ?? null;

        $url = $this->config->getBaseUrl() . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $payload = null;
        if ($json !== null) {
            $payload = Json::encode($json);
            $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
        }

        /** @var array<string, string> $headers */
        $headers = array_merge(
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config->getApiKey(),
                'User-Agent' => $this->config->getUserAgent() ?? self::DEFAULT_USER_AGENT,
            ],
            $headers
        );

        $attempt = 0;
        $maxAttempts = max(1, $this->config->getMaxRetries() + 1);
        $retryDelay = 0.5;

        do {
            try {
                $response = $this->transport->send($method, $url, [
                    'headers' => $headers,
                    'body' => $payload,
                ]);
            } catch (Throwable $throwable) {
                if ($this->shouldRetryNetwork($method, $attempt, $maxAttempts)) {
                    $this->sleep($retryDelay);
                    ++$attempt;
                    $retryDelay *= 2;
                    continue;
                }

                throw new NetworkException('Network error while contacting the Mailrify API.', $throwable);
            }

            $status = $response->getStatusCode();
            $body = $response->getBody();
            $decoded = $this->decodeBody($body);

            if ($status >= 200 && $status < 300) {
                $this->debugLog($method, $url, $headers, $payload, $status, $body);
                return $decoded;
            }

            if ($this->shouldRetryHttp($method, $status, $attempt, $maxAttempts)) {
                $retryAfter = $this->retryAfterSeconds($response->getHeaders());
                if ($retryAfter !== null) {
                    $this->sleep((float) $retryAfter);
                } else {
                    $this->sleep($retryDelay);
                    $retryDelay *= 2;
                }
                ++$attempt;
                continue;
            }

            $this->debugLog($method, $url, $headers, $payload, $status, $body);
            $this->throwForError($response, $decoded, $body);
        } while ($attempt < $maxAttempts);

        throw new MailrifyException('Exceeded maximum retry attempts when calling the Mailrify API.');
    }

    private function decodeBody(string $body): mixed
    {
        if ($body === '') {
            return null;
        }

        return Json::decode($body);
    }

    /** @param array<string, list<string>|string> $headers */
    private function retryAfterSeconds(array $headers): ?int
    {
        $header = $this->headerValue($headers, 'retry-after');
        if ($header === null) {
            return null;
        }

        if (is_numeric($header)) {
            return (int) $header;
        }

        $timestamp = strtotime($header);
        if ($timestamp === false) {
            return null;
        }

        $diff = $timestamp - time();
        return $diff > 0 ? $diff : null;
    }

    private function sleep(float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        usleep((int) round($seconds * 1_000_000));
    }

    private function shouldRetryNetwork(string $method, int $attempt, int $maxAttempts): bool
    {
        return $attempt < $maxAttempts - 1 && in_array($method, ['GET', 'HEAD'], true);
    }

    private function shouldRetryHttp(string $method, int $status, int $attempt, int $maxAttempts): bool
    {
        if ($attempt >= $maxAttempts - 1) {
            return false;
        }

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($status === 429) {
            return true;
        }

        return $status >= 500;
    }

    /** @param mixed $decoded */
    private function throwForError(Response $response, mixed $decoded, string $rawBody): never
    {
        $status = $response->getStatusCode();
        $headers = $response->getHeaders();
        $requestId = $this->headerValue($headers, 'x-request-id');
        $errorCode = is_array($decoded) && isset($decoded['code']) && is_string($decoded['code']) ? $decoded['code'] : null;
        $message = $this->extractErrorMessage($decoded, $status);

        if ($status === 401 || $status === 403) {
            throw new AuthException($status, $message, $errorCode, $requestId, $decoded, $headers);
        }

        if ($status === 429) {
            $retryAfter = $this->retryAfterSeconds($headers);
            throw new RateLimitException($status, $message, $retryAfter, $errorCode, $requestId, $decoded, $headers);
        }

        throw new ApiException($status, $message, $errorCode, $requestId, $decoded !== null ? $decoded : $rawBody, $headers);
    }

    private function extractErrorMessage(mixed $decoded, int $status): string
    {
        if (is_array($decoded)) {
            $error = $decoded['error'] ?? $decoded['message'] ?? null;
            if (is_string($error) && $error !== '') {
                return $error;
            }
        }

        return sprintf('Mailrify API request failed with status %d.', $status);
    }

    /** @param array<string, list<string>|string> $headers */
    private function headerValue(array $headers, string $name): ?string
    {
        $lower = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $lower) {
                if (is_array($value)) {
                    return $value[0] ?? null;
                }

                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $headers
     */
    private function debugLog(string $method, string $url, array $headers, ?string $payload, int $status, string $responseBody): void
    {
        if (!$this->config->isDebug()) {
            return;
        }

        $sanitizedHeaders = $headers;
        if (isset($sanitizedHeaders['Authorization'])) {
            $sanitizedHeaders['Authorization'] = 'Bearer ***';
        }

        $message = sprintf(
            '[Mailrify SDK] %s %s -> %d; request=%s; response=%s',
            $method,
            $url,
            $status,
            json_encode([
                'headers' => $sanitizedHeaders,
                'bodyLength' => $payload !== null ? strlen($payload) : null,
            ], JSON_PRESERVE_ZERO_FRACTION),
            json_encode([
                'bodyLength' => strlen($responseBody),
            ], JSON_PRESERVE_ZERO_FRACTION)
        );

        error_log($message);
    }
}
