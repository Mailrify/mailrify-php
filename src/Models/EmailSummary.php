<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Models\Enums\EmailStatus;
use Mailrify\Sdk\Utils\Cast;

/**
 * @psalm-type AddressValue = string|list<string>
 */
final class EmailSummary
{
    /**
     * @param AddressValue      $to
     * @param AddressValue|null $replyTo
     * @param AddressValue|null $cc
     * @param AddressValue|null $bcc
     */
    public function __construct(
        public readonly string $id,
        public readonly string|array $to,
        public readonly string|array|null $replyTo,
        public readonly string|array|null $cc,
        public readonly string|array|null $bcc,
        public readonly string $from,
        public readonly string $subject,
        public readonly ?string $html,
        public readonly ?string $text,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?EmailStatus $latestStatus,
        public readonly ?string $scheduledAt,
        public readonly ?int $domainId
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $status = null;
        if (isset($data['latestStatus']) && is_string($data['latestStatus'])) {
            $status = EmailStatus::from($data['latestStatus']);
        }

        return new self(
            id: Cast::string($data['id'] ?? null),
            to: self::normalizeRequiredAddress($data['to'] ?? null),
            replyTo: self::normalizeOptionalAddress($data['replyTo'] ?? null),
            cc: self::normalizeOptionalAddress($data['cc'] ?? null),
            bcc: self::normalizeOptionalAddress($data['bcc'] ?? null),
            from: Cast::string($data['from'] ?? null),
            subject: Cast::string($data['subject'] ?? null),
            html: Cast::nullableString($data['html'] ?? null),
            text: Cast::nullableString($data['text'] ?? null),
            createdAt: Cast::string($data['createdAt'] ?? null),
            updatedAt: Cast::string($data['updatedAt'] ?? null),
            latestStatus: $status,
            scheduledAt: Cast::nullableString($data['scheduledAt'] ?? null),
            domainId: Cast::nullableInt($data['domainId'] ?? null)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'to' => $this->to,
            'replyTo' => $this->replyTo,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'from' => $this->from,
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'latestStatus' => $this->latestStatus?->value,
            'scheduledAt' => $this->scheduledAt,
            'domainId' => $this->domainId,
        ];
    }

    /**
     * @return AddressValue
     */
    private static function normalizeRequiredAddress(mixed $value): string|array
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value)) {
            $addresses = [];
            foreach ($value as $entry) {
                if (is_string($entry) && $entry !== '') {
                    $addresses[] = $entry;
                }
            }

            return $addresses !== [] ? $addresses : '';
        }

        return '';
    }

    /**
     * @return AddressValue|null
     */
    private static function normalizeOptionalAddress(mixed $value): string|array|null
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value)) {
            $addresses = [];
            foreach ($value as $entry) {
                if (is_string($entry) && $entry !== '') {
                    $addresses[] = $entry;
                }
            }

            return $addresses !== [] ? $addresses : null;
        }

        return null;
    }
}
