<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class ListEmailsResponse
{
    /** @param list<EmailSummary> $data */
    public function __construct(
        public readonly array $data,
        public readonly int $count
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $items = [];
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $items[] = EmailSummary::fromArray($item);
                }
            }
        }

        return new self(
            data: $items,
            count: isset($payload['count']) ? Cast::int($payload['count']) : count($items)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (EmailSummary $summary): array => $summary->toArray(), $this->data),
            'count' => $this->count,
        ];
    }
}
