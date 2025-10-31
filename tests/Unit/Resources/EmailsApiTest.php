<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Models\Email;
use Mailrify\Sdk\Models\SendEmailResponse;
use Mailrify\Sdk\Resources\EmailsApi;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use Mailrify\Sdk\Tests\Fixtures\ResponseFactory;
use Mailrify\Sdk\Tests\Unit\TestCase;

final class EmailsApiTest extends TestCase
{
    public function testSendValidatesRequiredFields(): void
    {
        $api = new EmailsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['emailId' => 'abc']))));

        $this->expectException(ValidationException::class);
        $api->send(['from' => 'sender@example.com']);
    }

    public function testSendReturnsResponse(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['emailId' => 'email_123']));
        $api = new EmailsApi($this->createHttpClient($transport));

        $response = $api->send([
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'subject' => 'Hello',
        ]);

        self::assertInstanceOf(SendEmailResponse::class, $response);
        self::assertSame('email_123', $response->emailId);
        $body = $transport->calls[0]['options']['body'] ?? '';
        self::assertIsString($body);
        self::assertStringContainsString('"from":"sender@example.com"', $body);
    }

    public function testGetReturnsEmail(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, $this->emailPayload()));
        $api = new EmailsApi($this->createHttpClient($transport));

        $email = $api->get('email_123');

        self::assertInstanceOf(Email::class, $email);
        self::assertSame('email_123', $email->id);
        self::assertSame('https://api.test/v1/emails/email_123', $transport->calls[0]['url']);
    }

    public function testListAllSendsQueryParameters(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['data' => [], 'count' => 0]));
        $api = new EmailsApi($this->createHttpClient($transport));

        $api->listAll(['page' => 2, 'domainId' => '1']);

        self::assertSame('https://api.test/v1/emails?page=2&domainId=1', $transport->calls[0]['url']);
    }

    public function testUpdateScheduleValidatesInput(): void
    {
        $api = new EmailsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['emailId' => 'email_1']))));

        $this->expectException(ValidationException::class);
        $api->updateSchedule('email_1', '   ');
    }

    public function testCancelReturnsResponse(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['emailId' => 'email_1']));
        $api = new EmailsApi($this->createHttpClient($transport));

        $response = $api->cancel('email_1');

        self::assertSame('email_1', $response->emailId);
        self::assertSame('https://api.test/v1/emails/email_1/cancel', $transport->calls[0]['url']);
    }

    public function testBatchRequiresEmails(): void
    {
        $api = new EmailsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['data' => []]))));

        $this->expectException(ValidationException::class);
        $api->batch([]);
    }

    public function testBatchSendsPayload(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['data' => [['emailId' => 'e1']]]));
        $api = new EmailsApi($this->createHttpClient($transport));

        $api->batch([
            [
                'from' => 'sender@example.com',
                'to' => 'first@example.com',
                'subject' => 'First',
            ],
        ]);

        self::assertSame('POST', $transport->calls[0]['method']);
        $raw = $transport->calls[0]['options']['body'] ?? '[]';
        self::assertIsString($raw);
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey(0, $payload);
        self::assertIsArray($payload[0]);
        self::assertSame('first@example.com', $payload[0]['to']);
    }

    /**
     * @return array<string, mixed>
     */
    private function emailPayload(): array
    {
        return [
            'id' => 'email_123',
            'teamId' => 1,
            'to' => 'recipient@example.com',
            'replyTo' => null,
            'cc' => null,
            'bcc' => null,
            'from' => 'sender@example.com',
            'subject' => 'Welcome',
            'html' => '<p>Hello</p>',
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
            'emailEvents' => [[
                'emailId' => 'email_123',
                'status' => 'SENT',
                'createdAt' => '2024-01-01T00:00:00Z',
                'data' => null,
            ]],
        ];
    }
}
