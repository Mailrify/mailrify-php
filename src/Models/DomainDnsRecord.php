<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Models\Enums\DomainDnsRecordStatus;
use Mailrify\Sdk\Utils\Cast;

final class DomainDnsRecord
{
    public function __construct(
        public readonly string $type,
        public readonly string $name,
        public readonly string $value,
        public readonly string $ttl,
        public readonly ?string $priority,
        public readonly DomainDnsRecordStatus $status,
        public readonly bool $recommended
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $statusValue = isset($data['status']) && is_string($data['status']) ? $data['status'] : DomainDnsRecordStatus::NOT_STARTED->value;

        return new self(
            type: Cast::string($data['type'] ?? null),
            name: Cast::string($data['name'] ?? null),
            value: Cast::string($data['value'] ?? null),
            ttl: Cast::string($data['ttl'] ?? null),
            priority: Cast::nullableString($data['priority'] ?? null),
            status: DomainDnsRecordStatus::from($statusValue),
            recommended: Cast::bool($data['recommended'] ?? null)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'value' => $this->value,
            'ttl' => $this->ttl,
            'priority' => $this->priority,
            'status' => $this->status->value,
            'recommended' => $this->recommended,
        ];
    }
}
