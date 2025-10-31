<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Models\Campaign;
use Mailrify\Sdk\Models\ScheduleCampaignResponse;
use Mailrify\Sdk\Models\SuccessResponse;
use Mailrify\Sdk\Utils\Cast;

final class CampaignsApi
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Campaign
    {
        $payload = $this->prepareCreatePayload($data);

        $response = $this->httpClient->request('POST', '/v1/campaigns', [
            'json' => $payload,
        ]);

        return Campaign::fromArray(is_array($response) ? $response : []);
    }

    public function get(string $campaignId): Campaign
    {
        $response = $this->httpClient->request('GET', sprintf('/v1/campaigns/%s', rawurlencode($campaignId)));

        return Campaign::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param array{scheduledAt?: string, batchSize?: int} $data
     */
    public function schedule(string $campaignId, array $data): ScheduleCampaignResponse
    {
        if (!array_key_exists('scheduledAt', $data) && !array_key_exists('batchSize', $data)) {
            throw new ValidationException('At least "scheduledAt" or "batchSize" must be provided when scheduling a campaign.');
        }

        $payload = [];
        if (array_key_exists('scheduledAt', $data)) {
            $scheduledAt = $data['scheduledAt'];
            if (!is_string($scheduledAt) || trim($scheduledAt) === '') {
                throw new ValidationException('scheduledAt cannot be empty when provided.');
            }
            $payload['scheduledAt'] = $scheduledAt;
        }

        if (array_key_exists('batchSize', $data)) {
            $batchSize = Cast::int($data['batchSize']);
            if ($batchSize < 1) {
                throw new ValidationException('batchSize must be greater than zero when provided.');
            }
            $payload['batchSize'] = $batchSize;
        }

        $response = $this->httpClient->request(
            'POST',
            sprintf('/v1/campaigns/%s/schedule', rawurlencode($campaignId)),
            ['json' => $payload]
        );

        return ScheduleCampaignResponse::fromArray(is_array($response) ? $response : []);
    }

    public function pause(string $campaignId): SuccessResponse
    {
        $response = $this->httpClient->request('POST', sprintf('/v1/campaigns/%s/pause', rawurlencode($campaignId)));

        return SuccessResponse::fromArray(is_array($response) ? $response : []);
    }

    public function resume(string $campaignId): SuccessResponse
    {
        $response = $this->httpClient->request('POST', sprintf('/v1/campaigns/%s/resume', rawurlencode($campaignId)));

        return SuccessResponse::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareCreatePayload(array $data): array
    {
        $requiredKeys = ['name', 'from', 'subject', 'contactBookId'];
        $payload = [];

        foreach ($requiredKeys as $key) {
            $value = isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
            if ($value === '') {
                throw new ValidationException(sprintf('The "%s" field is required when creating a campaign.', $key));
            }
            $payload[$key] = $value;
        }

        if (array_key_exists('previewText', $data)) {
            $payload['previewText'] = Cast::nullableString($data['previewText']);
        }

        if (array_key_exists('content', $data)) {
            $payload['content'] = Cast::nullableString($data['content']);
        }

        if (array_key_exists('html', $data)) {
            $payload['html'] = Cast::nullableString($data['html']);
        }

        foreach (['replyTo', 'cc', 'bcc'] as $addressKey) {
            if (array_key_exists($addressKey, $data)) {
                $normalized = $this->normalizeAddressList($data[$addressKey]);
                if ($normalized !== null) {
                    $payload[$addressKey] = $normalized;
                }
            }
        }

        if (array_key_exists('sendNow', $data)) {
            $payload['sendNow'] = Cast::bool($data['sendNow']);
        }

        if (array_key_exists('scheduledAt', $data)) {
            $scheduledAt = Cast::nullableString($data['scheduledAt']);
            if ($scheduledAt !== null) {
                $payload['scheduledAt'] = $scheduledAt;
            }
        }

        if (array_key_exists('batchSize', $data)) {
            $batchSize = Cast::int($data['batchSize']);
            if ($batchSize < 1) {
                throw new ValidationException('batchSize must be greater than zero when provided.');
            }
            $payload['batchSize'] = $batchSize;
        }

        return $payload;
    }

    /**
     * @return list<string>|string|null
     */
    private function normalizeAddressList(mixed $value): array|string|null
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (!is_array($value)) {
            return null;
        }

        $addresses = [];
        foreach ($value as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $addresses[] = trim($entry);
            }
        }

        return $addresses !== [] ? $addresses : null;
    }
}
