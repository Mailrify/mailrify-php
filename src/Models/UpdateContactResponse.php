<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class UpdateContactResponse
{
    public function __construct(public readonly string $contactId)
    {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(Cast::string($payload['contactId'] ?? null));
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['contactId' => $this->contactId];
    }
}
