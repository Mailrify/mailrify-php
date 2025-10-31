<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit\Http;

use Mailrify\Sdk\Exceptions\AuthException;
use Mailrify\Sdk\Exceptions\NetworkException;
use Mailrify\Sdk\Exceptions\RateLimitException;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use Mailrify\Sdk\Tests\Fixtures\ResponseFactory;
use Mailrify\Sdk\Tests\Unit\TestCase;
use RuntimeException;

final class HttpClientTest extends TestCase
{
    public function testSuccessfulRequestReturnsDecodedJson(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['ok' => true]));
        $client = $this->createHttpClient($transport);

        $payload = $client->request('GET', '/v1/domains');

        self::assertSame(['ok' => true], $payload);
        self::assertCount(1, $transport->calls);
        self::assertSame('GET', $transport->calls[0]['method']);
        self::assertSame('https://api.test/v1/domains', $transport->calls[0]['url']);
        $options = $transport->calls[0]['options'] ?? null;
        self::assertIsArray($options);
        self::assertArrayHasKey('headers', $options);
        self::assertIsArray($options['headers']);
        self::assertArrayHasKey('Authorization', $options['headers']);
    }

    public function testAuthErrorThrowsAuthException(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(401, ['error' => 'Unauthorized']));
        $client = $this->createHttpClient($transport);

        $this->expectException(AuthException::class);
        $client->request('GET', '/v1/domains');
    }

    public function testRateLimitThrowsRateLimitException(): void
    {
        $transport = new FakeTransport(
            ResponseFactory::json(429, ['error' => 'Too many requests'], ['Retry-After' => '3']),
            ResponseFactory::json(429, ['error' => 'Too many requests'], ['Retry-After' => '3']),
            ResponseFactory::json(429, ['error' => 'Too many requests'], ['Retry-After' => '3'])
        );
        $client = $this->createHttpClient($transport);

        try {
            $client->request('GET', '/v1/domains');
            self::fail('RateLimitException was not thrown.');
        } catch (RateLimitException $exception) {
            self::assertSame(429, $exception->getStatusCode());
            self::assertSame(3, $exception->getRetryAfter());
        }
    }

    public function testNetworkExceptionIsWrapped(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, []));
        $transport->exception = new RuntimeException('boom');
        $client = $this->createHttpClient($transport);

        $this->expectException(NetworkException::class);
        $client->request('GET', '/v1/domains');
    }

    public function testServerErrorRetriesAndSucceeds(): void
    {
        $transport = new FakeTransport(
            ResponseFactory::json(500, ['error' => 'Internal error']),
            ResponseFactory::json(200, ['ok' => true])
        );
        $client = $this->createHttpClient($transport);

        $payload = $client->request('GET', '/v1/domains');

        self::assertSame(['ok' => true], $payload);
        self::assertCount(2, $transport->calls);
    }
}
