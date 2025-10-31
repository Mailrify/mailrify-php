<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Models\Domain;
use Mailrify\Sdk\Resources\DomainsApi;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use Mailrify\Sdk\Tests\Fixtures\ResponseFactory;
use Mailrify\Sdk\Tests\Unit\TestCase;

final class DomainsApiTest extends TestCase
{
    public function testListAllReturnsDomains(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, [$this->domainPayload()]));
        $api = new DomainsApi($this->createHttpClient($transport));

        $domains = $api->listAll();

        self::assertCount(1, $domains);
        self::assertInstanceOf(Domain::class, $domains[0]);
        self::assertSame('https://api.test/v1/domains', $transport->calls[0]['url']);
        self::assertSame('GET', $transport->calls[0]['method']);
    }

    public function testCreateValidatesName(): void
    {
        $api = new DomainsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, $this->domainPayload()))));

        $this->expectException(ValidationException::class);
        $api->create(['region' => 'us-east-1']);
    }

    public function testCreateSendsRequest(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, $this->domainPayload()));
        $api = new DomainsApi($this->createHttpClient($transport));

        $api->create(['name' => 'example.com', 'region' => 'us-east-1']);

        self::assertSame('POST', $transport->calls[0]['method']);
        self::assertSame('https://api.test/v1/domains', $transport->calls[0]['url']);
        $body = $transport->calls[0]['options']['body'] ?? null;
        self::assertIsString($body);
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['name' => 'example.com', 'region' => 'us-east-1'], $decoded);
    }

    public function testGetDomain(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, $this->domainPayload()));
        $api = new DomainsApi($this->createHttpClient($transport));

        $domain = $api->get(5);

        self::assertInstanceOf(Domain::class, $domain);
        self::assertSame('https://api.test/v1/domains/5', $transport->calls[0]['url']);
    }

    public function testVerifyDomain(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['message' => 'ok']));
        $api = new DomainsApi($this->createHttpClient($transport));

        $response = $api->verify(42);

        self::assertSame('ok', $response->message);
        self::assertSame('https://api.test/v1/domains/42/verify', $transport->calls[0]['url']);
    }

    public function testDeleteDomain(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['id' => 42, 'success' => true, 'message' => 'deleted']));
        $api = new DomainsApi($this->createHttpClient($transport));

        $response = $api->delete(42);

        self::assertTrue($response->success);
        self::assertSame(42, $response->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function domainPayload(): array
    {
        return [
            'id' => 1,
            'name' => 'example.com',
            'teamId' => 7,
            'status' => 'PENDING',
            'region' => 'us-east-1',
            'clickTracking' => true,
            'openTracking' => false,
            'publicKey' => 'pk_123',
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
            'dmarcAdded' => false,
            'isVerifying' => false,
            'dnsRecords' => [[
                'type' => 'MX',
                'name' => 'mail',
                'value' => 'value',
                'ttl' => 'Auto',
                'status' => 'PENDING',
                'recommended' => true,
            ]],
        ];
    }
}
