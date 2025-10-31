<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Models\DeleteDomainResponse;
use Mailrify\Sdk\Models\Domain;
use Mailrify\Sdk\Models\VerifyDomainResponse;

final class DomainsApi
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /** @return list<Domain> */
    public function listAll(): array
    {
        $payload = $this->httpClient->request('GET', '/v1/domains');

        $domains = [];
        if (is_array($payload)) {
            foreach ($payload as $domain) {
                if (is_array($domain)) {
                    /** @var array<string, mixed> $domain */
                    $domains[] = Domain::fromArray($domain);
                }
            }
        }

        return $domains;
    }

    /**
     * @param array{name?: string, region?: string} $data
     */
    public function create(array $data): Domain
    {
        $name = isset($data['name']) && is_string($data['name']) ? trim($data['name']) : '';
        $region = isset($data['region']) && is_string($data['region']) ? trim($data['region']) : '';

        if ($name === '') {
            throw new ValidationException('The "name" field is required when creating a domain.');
        }

        if ($region === '') {
            throw new ValidationException('The "region" field is required when creating a domain.');
        }

        $payload = $this->httpClient->request('POST', '/v1/domains', [
            'json' => [
                'name' => $name,
                'region' => $region,
            ],
        ]);

        return Domain::fromArray(is_array($payload) ? $payload : []);
    }

    public function get(int|string $domainId): Domain
    {
        $payload = $this->httpClient->request('GET', sprintf('/v1/domains/%s', rawurlencode((string) $domainId)));

        return Domain::fromArray(is_array($payload) ? $payload : []);
    }

    public function delete(int|string $domainId): DeleteDomainResponse
    {
        $payload = $this->httpClient->request('DELETE', sprintf('/v1/domains/%s', rawurlencode((string) $domainId)));

        return DeleteDomainResponse::fromArray(is_array($payload) ? $payload : []);
    }

    public function verify(int|string $domainId): VerifyDomainResponse
    {
        $payload = $this->httpClient->request('PUT', sprintf('/v1/domains/%s/verify', rawurlencode((string) $domainId)));

        return VerifyDomainResponse::fromArray(is_array($payload) ? $payload : []);
    }
}
