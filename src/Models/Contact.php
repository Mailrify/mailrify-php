<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Utils\Cast;

final class Contact
{
    /** @param array<string, string> $properties */
    public function __construct(
        public readonly string $id,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly string $email,
        public readonly bool $subscribed,
        public readonly array $properties,
        public readonly string $contactBookId,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::string($data['id'] ?? null),
            firstName: Cast::nullableString($data['firstName'] ?? null),
            lastName: Cast::nullableString($data['lastName'] ?? null),
            email: Cast::string($data['email'] ?? null),
            subscribed: Cast::bool($data['subscribed'] ?? null, true),
            properties: Cast::stringMap($data['properties'] ?? null),
            contactBookId: Cast::string($data['contactBookId'] ?? null),
            createdAt: Cast::string($data['createdAt'] ?? null),
            updatedAt: Cast::string($data['updatedAt'] ?? null)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'subscribed' => $this->subscribed,
            'properties' => $this->properties,
            'contactBookId' => $this->contactBookId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
