<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class VerifyDomainResponse
{
    public function __construct(public readonly string $message)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(Cast::string($data['message'] ?? null));
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['message' => $this->message];
    }
}
