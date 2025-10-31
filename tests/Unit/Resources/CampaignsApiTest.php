<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Models\Campaign;
use Mailrify\Sdk\Models\ScheduleCampaignResponse;
use Mailrify\Sdk\Resources\CampaignsApi;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use Mailrify\Sdk\Tests\Fixtures\ResponseFactory;
use Mailrify\Sdk\Tests\Unit\TestCase;

final class CampaignsApiTest extends TestCase
{
    public function testCreateValidatesRequiredFields(): void
    {
        $api = new CampaignsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, $this->campaignPayload()))));

        $this->expectException(ValidationException::class);
        $api->create(['name' => 'Newsletter']);
    }

    public function testCreateReturnsCampaign(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, $this->campaignPayload()));
        $api = new CampaignsApi($this->createHttpClient($transport));

        $campaign = $api->create([
            'name' => 'Newsletter',
            'from' => 'sender@example.com',
            'subject' => 'Subject',
            'contactBookId' => 'book_1',
        ]);

        self::assertInstanceOf(Campaign::class, $campaign);
        self::assertSame('campaign_1', $campaign->id);
        self::assertSame('https://api.test/v1/campaigns', $transport->calls[0]['url']);
    }

    public function testScheduleValidatesInput(): void
    {
        $api = new CampaignsApi($this->createHttpClient(new FakeTransport(ResponseFactory::json(200, ['success' => true]))));

        $this->expectException(ValidationException::class);
        $api->schedule('campaign_1', []);
    }

    public function testScheduleReturnsResponse(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, ['success' => true]));
        $api = new CampaignsApi($this->createHttpClient($transport));

        $response = $api->schedule('campaign_1', ['scheduledAt' => '2024-02-01T10:00:00Z']);

        self::assertInstanceOf(ScheduleCampaignResponse::class, $response);
        self::assertTrue($response->success);
        self::assertSame('https://api.test/v1/campaigns/campaign_1/schedule', $transport->calls[0]['url']);
    }

    public function testPauseAndResumeReturnSuccess(): void
    {
        $transport = new FakeTransport(
            ResponseFactory::json(200, ['success' => true]),
            ResponseFactory::json(200, ['success' => true])
        );
        $api = new CampaignsApi($this->createHttpClient($transport));

        $pause = $api->pause('campaign_1');
        $resume = $api->resume('campaign_1');

        self::assertTrue($pause->success);
        self::assertTrue($resume->success);
        self::assertSame('https://api.test/v1/campaigns/campaign_1/pause', $transport->calls[0]['url']);
        self::assertSame('https://api.test/v1/campaigns/campaign_1/resume', $transport->calls[1]['url']);
    }

    public function testGetReturnsCampaign(): void
    {
        $transport = new FakeTransport(ResponseFactory::json(200, $this->campaignPayload()));
        $api = new CampaignsApi($this->createHttpClient($transport));

        $campaign = $api->get('campaign_1');

        self::assertInstanceOf(Campaign::class, $campaign);
        self::assertSame('https://api.test/v1/campaigns/campaign_1', $transport->calls[0]['url']);
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignPayload(): array
    {
        return [
            'id' => 'campaign_1',
            'name' => 'Newsletter',
            'from' => 'sender@example.com',
            'subject' => 'Subject',
            'previewText' => null,
            'contactBookId' => 'book_1',
            'html' => '<p>Hello</p>',
            'content' => 'Hello',
            'status' => 'draft',
            'scheduledAt' => null,
            'batchSize' => 100,
            'batchWindowMinutes' => 10,
            'total' => 0,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'unsubscribed' => 0,
            'bounced' => 0,
            'hardBounced' => 0,
            'complained' => 0,
            'replyTo' => [],
            'cc' => [],
            'bcc' => [],
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
        ];
    }
}
