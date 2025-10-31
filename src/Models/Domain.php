<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models;

use Mailrify\Sdk\Models\Enums\DomainStatus;
use Mailrify\Sdk\Utils\Cast;

final class Domain
{
    /** @param list<DomainDnsRecord> $dnsRecords */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $teamId,
        public readonly DomainStatus $status,
        public readonly string $region,
        public readonly bool $clickTracking,
        public readonly bool $openTracking,
        public readonly string $publicKey,
        public readonly ?string $dkimStatus,
        public readonly ?string $spfDetails,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly bool $dmarcAdded,
        public readonly bool $isVerifying,
        public readonly ?string $errorMessage,
        public readonly ?string $subdomain,
        public readonly ?string $verificationError,
        public readonly ?string $lastCheckedTime,
        public readonly array $dnsRecords
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $records = [];
        if (isset($data['dnsRecords']) && is_array($data['dnsRecords'])) {
            foreach ($data['dnsRecords'] as $record) {
                if (is_array($record)) {
                    /** @var array<string, mixed> $record */
                    $records[] = DomainDnsRecord::fromArray($record);
                }
            }
        }

        $statusValue = isset($data['status']) && is_string($data['status']) ? $data['status'] : DomainStatus::NOT_STARTED->value;

        return new self(
            id: Cast::int($data['id'] ?? null),
            name: Cast::string($data['name'] ?? null),
            teamId: Cast::int($data['teamId'] ?? null),
            status: DomainStatus::from($statusValue),
            region: Cast::string($data['region'] ?? null),
            clickTracking: Cast::bool($data['clickTracking'] ?? null),
            openTracking: Cast::bool($data['openTracking'] ?? null),
            publicKey: Cast::string($data['publicKey'] ?? null),
            dkimStatus: Cast::nullableString($data['dkimStatus'] ?? null),
            spfDetails: Cast::nullableString($data['spfDetails'] ?? null),
            createdAt: Cast::string($data['createdAt'] ?? null),
            updatedAt: Cast::string($data['updatedAt'] ?? null),
            dmarcAdded: Cast::bool($data['dmarcAdded'] ?? null),
            isVerifying: Cast::bool($data['isVerifying'] ?? null),
            errorMessage: Cast::nullableString($data['errorMessage'] ?? null),
            subdomain: Cast::nullableString($data['subdomain'] ?? null),
            verificationError: Cast::nullableString($data['verificationError'] ?? null),
            lastCheckedTime: Cast::nullableString($data['lastCheckedTime'] ?? null),
            dnsRecords: $records
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'teamId' => $this->teamId,
            'status' => $this->status->value,
            'region' => $this->region,
            'clickTracking' => $this->clickTracking,
            'openTracking' => $this->openTracking,
            'publicKey' => $this->publicKey,
            'dkimStatus' => $this->dkimStatus,
            'spfDetails' => $this->spfDetails,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'dmarcAdded' => $this->dmarcAdded,
            'isVerifying' => $this->isVerifying,
            'errorMessage' => $this->errorMessage,
            'subdomain' => $this->subdomain,
            'verificationError' => $this->verificationError,
            'lastCheckedTime' => $this->lastCheckedTime,
            'dnsRecords' => array_map(static fn (DomainDnsRecord $record): array => $record->toArray(), $this->dnsRecords),
        ];
    }
}
