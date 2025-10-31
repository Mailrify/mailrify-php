<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Models\Contact;
use Mailrify\Sdk\Models\CreateContactResponse;
use Mailrify\Sdk\Models\DeleteContactResponse;
use Mailrify\Sdk\Models\UpdateContactResponse;
use Mailrify\Sdk\Models\UpsertContactResponse;
use Mailrify\Sdk\Utils\Arr;
use Mailrify\Sdk\Utils\Cast;

final class ContactsApi
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param  array{emails?: string, page?: int, limit?: int, ids?: string} $filters
     * @return list<Contact>
     */
    public function listAll(string $contactBookId, array $filters = []): array
    {
        $query = Arr::filterNull([
            'emails' => isset($filters['emails']) && is_string($filters['emails']) ? $filters['emails'] : null,
            'page' => isset($filters['page']) ? Cast::nullableInt($filters['page']) : null,
            'limit' => isset($filters['limit']) ? Cast::nullableInt($filters['limit']) : null,
            'ids' => isset($filters['ids']) && is_string($filters['ids']) ? $filters['ids'] : null,
        ]);

        $response = $this->httpClient->request(
            'GET',
            sprintf('/v1/contactBooks/%s/contacts', rawurlencode($contactBookId)),
            ['query' => $query]
        );

        $items = [];
        if (is_array($response)) {
            foreach ($response as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $items[] = Contact::fromArray($item);
                }
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $contactBookId, array $data): CreateContactResponse
    {
        $payload = $this->prepareContactPayload($data, requireEmail: true);

        $response = $this->httpClient->request(
            'POST',
            sprintf('/v1/contactBooks/%s/contacts', rawurlencode($contactBookId)),
            ['json' => $payload]
        );

        return CreateContactResponse::fromArray(is_array($response) ? $response : []);
    }

    public function get(string $contactBookId, string $contactId): Contact
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('/v1/contactBooks/%s/contacts/%s', rawurlencode($contactBookId), rawurlencode($contactId))
        );

        return Contact::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $contactBookId, string $contactId, array $data): UpdateContactResponse
    {
        if ($data === []) {
            throw new ValidationException('At least one field is required to update a contact.');
        }

        $payload = $this->prepareContactPayload($data, requireEmail: false);

        $response = $this->httpClient->request(
            'PATCH',
            sprintf('/v1/contactBooks/%s/contacts/%s', rawurlencode($contactBookId), rawurlencode($contactId)),
            ['json' => $payload]
        );

        return UpdateContactResponse::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsert(string $contactBookId, string $contactId, array $data): UpsertContactResponse
    {
        $payload = $this->prepareContactPayload($data, requireEmail: true);

        $response = $this->httpClient->request(
            'PUT',
            sprintf('/v1/contactBooks/%s/contacts/%s', rawurlencode($contactBookId), rawurlencode($contactId)),
            ['json' => $payload]
        );

        return UpsertContactResponse::fromArray(is_array($response) ? $response : []);
    }

    public function delete(string $contactBookId, string $contactId): DeleteContactResponse
    {
        $response = $this->httpClient->request(
            'DELETE',
            sprintf('/v1/contactBooks/%s/contacts/%s', rawurlencode($contactBookId), rawurlencode($contactId))
        );

        return DeleteContactResponse::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareContactPayload(array $data, bool $requireEmail): array
    {
        $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : '';
        if ($requireEmail && $email === '') {
            throw new ValidationException('The "email" field is required for this operation.');
        }

        $payload = [];
        if ($email !== '') {
            $payload['email'] = $email;
        }

        if (array_key_exists('firstName', $data)) {
            $firstName = Cast::nullableString($data['firstName']);
            if ($firstName !== null) {
                $payload['firstName'] = $firstName;
            }
        }

        if (array_key_exists('lastName', $data)) {
            $lastName = Cast::nullableString($data['lastName']);
            if ($lastName !== null) {
                $payload['lastName'] = $lastName;
            }
        }

        if (array_key_exists('properties', $data)) {
            $properties = Cast::stringMap($data['properties']);
            if ($properties !== []) {
                $payload['properties'] = $properties;
            }
        }

        if (array_key_exists('subscribed', $data)) {
            $payload['subscribed'] = Cast::bool($data['subscribed'], true);
        }

        return $payload;
    }
}
