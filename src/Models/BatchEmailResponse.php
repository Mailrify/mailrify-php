<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

final class BatchEmailResponse
{
    /** @param list<BatchEmailResponseItem> $data */
    public function __construct(public readonly array $data)
    {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $items = [];
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $items[] = BatchEmailResponseItem::fromArray($item);
                }
            }
        }

        return new self($items);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (BatchEmailResponseItem $item): array => $item->toArray(), $this->data),
        ];
    }
}
