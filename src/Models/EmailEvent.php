<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Models\Enums\EmailStatus;
use Mailrify\Sdk\Utils\Cast;

final class EmailEvent
{
    public function __construct(
        public readonly string $emailId,
        public readonly EmailStatus $status,
        public readonly string $createdAt,
        public readonly mixed $data
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $statusValue = isset($data['status']) && is_string($data['status']) ? $data['status'] : EmailStatus::QUEUED->value;

        return new self(
            emailId: Cast::string($data['emailId'] ?? null),
            status: EmailStatus::from($statusValue),
            createdAt: Cast::string($data['createdAt'] ?? null),
            data: $data['data'] ?? null
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'emailId' => $this->emailId,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'data' => $this->data,
        ];
    }
}
