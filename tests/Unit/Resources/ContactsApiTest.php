<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Models\Contact;
use Mailrify\Sdk\Resources\ContactsApi;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use Mailrify\Sdk\Tests\Fixtures\ResponseFactory;
use Mailrify\Sdk\Tests\Unit\TestCase;

final class ContactsApiTest extends TestCase
{
    public function testListAllReturnsContacts(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, [$this->contactPayload()]));
        $api = new ContactsApi($this->createHttpClient($transport));

        $contacts = $api->listAll('book_1', ['page' => 2, 'limit' => 10]);

        self::assertCount(1, $contacts);
        self::assertInstanceOf(Contact::class, $contacts[0]);
        self::assertStringContainsString('/v1/contactBooks/book_1/contacts?page=2&limit=10', $transport->calls[0]['url']);
    }

    public function testCreateRequiresEmail(): void
    {
        $api = new ContactsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['contactId' => 'c1']))));

        $this->expectException(ValidationException::class);
        $api->create('book_1', []);
    }

    public function testCreateSendsPayload(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['contactId' => 'c1']));
        $api = new ContactsApi($this->createHttpClient($transport));

        $api->create('book_1', ['email' => 'user@example.com', 'firstName' => 'Jane']);

        $raw = $transport->calls[0]['options']['body'] ?? '{}';
        self::assertIsString($raw);
        $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertSame('user@example.com', $body['email']);
        self::assertSame('https://api.test/v1/contactBooks/book_1/contacts', $transport->calls[0]['url']);
    }

    public function testUpdateRequiresAtLeastOneField(): void
    {
        $api = new ContactsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['contactId' => 'c1']))));

        $this->expectException(ValidationException::class);
        $api->update('book_1', 'contact_1', []);
    }

    public function testUpsertReturnsResponse(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['contactId' => 'c1']));
        $api = new ContactsApi($this->createHttpClient($transport));

        $response = $api->upsert('book_1', 'contact_1', ['email' => 'user@example.com']);

        self::assertSame('c1', $response->contactId);
        self::assertSame('PUT', $transport->calls[0]['method']);
    }

    public function testDeleteReturnsSuccessResponse(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['success' => true]));
        $api = new ContactsApi($this->createHttpClient($transport));

        $response = $api->delete('book_1', 'contact_1');

        self::assertTrue($response->success);
        self::assertSame('https://api.test/v1/contactBooks/book_1/contacts/contact_1', $transport->calls[0]['url']);
    }

    /**
     * @return array<string, mixed>
     */
    private function contactPayload(): array
    {
        return [
            'id' => 'contact_1',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'user@example.com',
            'subscribed' => true,
            'properties' => ['city' => 'Lisbon'],
            'contactBookId' => 'book_1',
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
        ];
    }
}
