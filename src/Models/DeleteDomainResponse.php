<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class DeleteDomainResponse
{
    public function __construct(
        public readonly int $id,
        public readonly bool $success,
        public readonly string $message
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['id'] ?? null),
            success: Cast::bool($data['success'] ?? null),
            message: Cast::string($data['message'] ?? null)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
