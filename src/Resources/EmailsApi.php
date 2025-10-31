<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Resources;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Models\BatchEmailResponse;
use Mailrify\Sdk\Models\CancelScheduleResponse;
use Mailrify\Sdk\Models\Email;
use Mailrify\Sdk\Models\ListEmailsResponse;
use Mailrify\Sdk\Models\SendEmailResponse;
use Mailrify\Sdk\Models\UpdateScheduleResponse;
use Mailrify\Sdk\Utils\Arr;
use Mailrify\Sdk\Utils\Cast;

final class EmailsApi
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function send(array $data): SendEmailResponse
    {
        $payload = $this->prepareEmailPayload($data);

        $response = $this->httpClient->request('POST', '/v1/emails', [
            'json' => $payload,
        ]);

        return SendEmailResponse::fromArray(is_array($response) ? $response : []);
    }

    public function get(string $emailId): Email
    {
        $response = $this->httpClient->request('GET', sprintf('/v1/emails/%s', rawurlencode($emailId)));

        return Email::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param array{page?: string|int, limit?: string|int, startDate?: string, endDate?: string, domainId?: string|array<int|string, string>} $filters
     */
    public function listAll(array $filters = []): ListEmailsResponse
    {
        $query = [];
        if (array_key_exists('page', $filters) && is_scalar($filters['page'])) {
            $query['page'] = (string) $filters['page'];
        }
        if (array_key_exists('limit', $filters) && is_scalar($filters['limit'])) {
            $query['limit'] = (string) $filters['limit'];
        }
        if (array_key_exists('startDate', $filters) && is_string($filters['startDate'])) {
            $query['startDate'] = $filters['startDate'];
        }
        if (array_key_exists('endDate', $filters) && is_string($filters['endDate'])) {
            $query['endDate'] = $filters['endDate'];
        }
        if (array_key_exists('domainId', $filters) && (is_string($filters['domainId']) || is_array($filters['domainId']))) {
            $query['domainId'] = $filters['domainId'];
        }

        $query = Arr::filterNull($query);

        $response = $this->httpClient->request('GET', '/v1/emails', [
            'query' => $query,
        ]);

        return ListEmailsResponse::fromArray(is_array($response) ? $response : []);
    }

    public function updateSchedule(string $emailId, string $scheduledAt): UpdateScheduleResponse
    {
        if (trim($scheduledAt) === '') {
            throw new ValidationException('scheduledAt must be provided when updating a schedule.');
        }

        $response = $this->httpClient->request('PATCH', sprintf('/v1/emails/%s', rawurlencode($emailId)), [
            'json' => [
                'scheduledAt' => $scheduledAt,
            ],
        ]);

        return UpdateScheduleResponse::fromArray(is_array($response) ? $response : []);
    }

    public function cancel(string $emailId): CancelScheduleResponse
    {
        $response = $this->httpClient->request('POST', sprintf('/v1/emails/%s/cancel', rawurlencode($emailId)));

        return CancelScheduleResponse::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param list<array<string, mixed>> $emails
     */
    public function batch(array $emails): BatchEmailResponse
    {
        if ($emails === []) {
            throw new ValidationException('At least one email must be provided for batch sending.');
        }

        $payload = array_map(fn (array $email): array => $this->prepareEmailPayload($email), $emails);

        $response = $this->httpClient->request('POST', '/v1/emails/batch', [
            'json' => $payload,
        ]);

        return BatchEmailResponse::fromArray(is_array($response) ? $response : []);
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareEmailPayload(array $data): array
    {
        $from = isset($data['from']) && is_string($data['from']) ? trim($data['from']) : '';
        $to = $data['to'] ?? null;

        if ($from === '') {
            throw new ValidationException('The "from" field is required when sending an email.');
        }

        $normalizedTo = self::normalizeAddress($to);
        if ($normalizedTo === null) {
            throw new ValidationException('The "to" field is required when sending an email.');
        }

        $payload = [
            'from' => $from,
            'to' => $normalizedTo,
        ];

        if (array_key_exists('subject', $data) && is_string($data['subject'])) {
            $payload['subject'] = $data['subject'];
        }

        if (array_key_exists('templateId', $data) && is_string($data['templateId'])) {
            $payload['templateId'] = $data['templateId'];
        }

        if (array_key_exists('variables', $data)) {
            $payload['variables'] = Cast::stringMap($data['variables']);
        }

        foreach (['replyTo', 'cc', 'bcc'] as $addressKey) {
            if (array_key_exists($addressKey, $data)) {
                $normalized = self::normalizeAddress($data[$addressKey]);
                if ($normalized !== null) {
                    $payload[$addressKey] = $normalized;
                }
            }
        }

        if (array_key_exists('text', $data) && (is_string($data['text']) || $data['text'] === null)) {
            $payload['text'] = $data['text'];
        }

        if (array_key_exists('html', $data) && (is_string($data['html']) || $data['html'] === null)) {
            $payload['html'] = $data['html'];
        }

        if (array_key_exists('headers', $data)) {
            $headers = Cast::stringMap($data['headers']);
            if ($headers !== []) {
                $payload['headers'] = $headers;
            }
        }

        if (array_key_exists('attachments', $data) && is_array($data['attachments'])) {
            $payload['attachments'] = $data['attachments'];
        }

        if (array_key_exists('scheduledAt', $data) && is_string($data['scheduledAt'])) {
            $payload['scheduledAt'] = $data['scheduledAt'];
        }

        if (array_key_exists('inReplyToId', $data) && (is_string($data['inReplyToId']) || $data['inReplyToId'] === null)) {
            $payload['inReplyToId'] = $data['inReplyToId'];
        }

        return $payload;
    }

    /**
     * @return array<int, string>|string|null
     */
    private static function normalizeAddress(mixed $value): array|string|null
    {
        if (is_string($value)) {
            return trim($value) !== '' ? $value : null;
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
