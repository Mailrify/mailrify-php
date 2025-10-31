<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

/**
 * @psalm-type AddressValue = string|list<string>
 */
final class Email
{
    /**
     * @param AddressValue      $to
     * @param AddressValue|null $replyTo
     * @param AddressValue|null $cc
     * @param AddressValue|null $bcc
     * @param list<EmailEvent>  $emailEvents
     */
    public function __construct(
        public readonly string $id,
        public readonly int $teamId,
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
        public readonly array $emailEvents
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $events = [];
        if (isset($data['emailEvents']) && is_array($data['emailEvents'])) {
            foreach ($data['emailEvents'] as $event) {
                if (is_array($event)) {
                    /** @var array<string, mixed> $event */
                    $events[] = EmailEvent::fromArray($event);
                }
            }
        }

        return new self(
            id: Cast::string($data['id'] ?? null),
            teamId: Cast::int($data['teamId'] ?? null),
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
            emailEvents: $events
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'teamId' => $this->teamId,
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
            'emailEvents' => array_map(static fn (EmailEvent $event): array => $event->toArray(), $this->emailEvents),
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
