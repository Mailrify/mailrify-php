<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class ScheduleCampaignResponse
{
    public function __construct(public readonly bool $success)
    {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(Cast::bool($payload['success'] ?? null));
    }

    /** @return array<string, bool> */
    public function toArray(): array
    {
        return ['success' => $this->success];
    }
}
