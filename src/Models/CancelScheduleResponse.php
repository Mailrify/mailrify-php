<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class CancelScheduleResponse
{
    public function __construct(public readonly string $emailId)
    {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(Cast::string($payload['emailId'] ?? null));
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['emailId' => $this->emailId];
    }
}
