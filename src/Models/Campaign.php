<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class Campaign
{
    /**
     * @param list<string> $replyTo
     * @param list<string> $cc
     * @param list<string> $bcc
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $from,
        public readonly string $subject,
        public readonly ?string $previewText,
        public readonly ?string $contactBookId,
        public readonly ?string $content,
        public readonly ?string $html,
        public readonly string $status,
        public readonly ?string $scheduledAt,
        public readonly ?int $batchSize,
        public readonly ?int $batchWindowMinutes,
        public readonly int $total,
        public readonly int $sent,
        public readonly int $delivered,
        public readonly int $opened,
        public readonly int $clicked,
        public readonly int $unsubscribed,
        public readonly int $bounced,
        public readonly int $hardBounced,
        public readonly int $complained,
        public readonly array $replyTo,
        public readonly array $cc,
        public readonly array $bcc,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: Cast::string($payload['id'] ?? null),
            name: Cast::string($payload['name'] ?? null),
            from: Cast::string($payload['from'] ?? null),
            subject: Cast::string($payload['subject'] ?? null),
            previewText: Cast::nullableString($payload['previewText'] ?? null),
            contactBookId: Cast::nullableString($payload['contactBookId'] ?? null),
            content: Cast::nullableString($payload['content'] ?? null),
            html: Cast::nullableString($payload['html'] ?? null),
            status: Cast::string($payload['status'] ?? null),
            scheduledAt: Cast::nullableString($payload['scheduledAt'] ?? null),
            batchSize: Cast::nullableInt($payload['batchSize'] ?? null),
            batchWindowMinutes: Cast::nullableInt($payload['batchWindowMinutes'] ?? null),
            total: Cast::int($payload['total'] ?? null),
            sent: Cast::int($payload['sent'] ?? null),
            delivered: Cast::int($payload['delivered'] ?? null),
            opened: Cast::int($payload['opened'] ?? null),
            clicked: Cast::int($payload['clicked'] ?? null),
            unsubscribed: Cast::int($payload['unsubscribed'] ?? null),
            bounced: Cast::int($payload['bounced'] ?? null),
            hardBounced: Cast::int($payload['hardBounced'] ?? null),
            complained: Cast::int($payload['complained'] ?? null),
            replyTo: self::normalizeAddresses($payload['replyTo'] ?? []),
            cc: self::normalizeAddresses($payload['cc'] ?? []),
            bcc: self::normalizeAddresses($payload['bcc'] ?? []),
            createdAt: Cast::string($payload['createdAt'] ?? null),
            updatedAt: Cast::string($payload['updatedAt'] ?? null)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'from' => $this->from,
            'subject' => $this->subject,
            'previewText' => $this->previewText,
            'contactBookId' => $this->contactBookId,
            'content' => $this->content,
            'html' => $this->html,
            'status' => $this->status,
            'scheduledAt' => $this->scheduledAt,
            'batchSize' => $this->batchSize,
            'batchWindowMinutes' => $this->batchWindowMinutes,
            'total' => $this->total,
            'sent' => $this->sent,
            'delivered' => $this->delivered,
            'opened' => $this->opened,
            'clicked' => $this->clicked,
            'unsubscribed' => $this->unsubscribed,
            'bounced' => $this->bounced,
            'hardBounced' => $this->hardBounced,
            'complained' => $this->complained,
            'replyTo' => $this->replyTo,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * @param  mixed        $addresses
     * @return list<string>
     */
    private static function normalizeAddresses(mixed $addresses): array
    {
        if (is_string($addresses) && $addresses !== '') {
            return [$addresses];
        }

        if (!is_array($addresses)) {
            return [];
        }

        $normalized = [];
        foreach ($addresses as $address) {
            if (is_string($address) && $address !== '') {
                $normalized[] = $address;
            }
        }

        return $normalized;
    }
}
