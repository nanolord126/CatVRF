<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Payment Rule Entity for ФЗ-161 compliance.
 * 
 * Stores versioned payment system rules as required by federal law.
 * All rules must be exportable for regulatory audits.
 */
final readonly class PaymentRule
{
    private function __construct(
        public string $uuid,
        public string $code,
        public string $name,
        public string $description,
        public string $category, // 'transaction_limits', 'fraud_detection', 'settlement', 'reporting'
        public array $ruleData, // JSON with actual rule parameters
        public string $version,
        public bool $isActive,
        public CarbonImmutable $effectiveFrom,
        public ?CarbonImmutable $effectiveTo,
        public string $createdBy,
        public CarbonImmutable $createdAt,
        public ?string $previousVersionUuid,
    ) {}

    public static function create(
        string $code,
        string $name,
        string $description,
        string $category,
        array $ruleData,
        string $createdBy,
        ?string $previousVersionUuid = null,
    ): self {
        $now = CarbonImmutable::now();
        
        return new self(
            uuid: Str::uuid()->toString(),
            code: $code,
            name: $name,
            description: $description,
            category: $category,
            ruleData: $ruleData,
            version: $previousVersionUuid ? self::getNextVersion($code, $previousVersionUuid) : '1.0.0',
            isActive: true,
            effectiveFrom: $now,
            effectiveTo: null,
            createdBy: $createdBy,
            createdAt: $now,
            previousVersionUuid: $previousVersionUuid,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            code: $data['code'],
            name: $data['name'],
            description: $data['description'],
            category: $data['category'],
            ruleData: $data['rule_data'],
            version: $data['version'],
            isActive: (bool) $data['is_active'],
            effectiveFrom: CarbonImmutable::parse($data['effective_from']),
            effectiveTo: $data['effective_to'] ? CarbonImmutable::parse($data['effective_to']) : null,
            createdBy: $data['created_by'],
            createdAt: CarbonImmutable::parse($data['created_at']),
            previousVersionUuid: $data['previous_version_uuid'] ?? null,
        );
    }

    public function deactivate(): self
    {
        return new self(
            uuid: $this->uuid,
            code: $this->code,
            name: $this->name,
            description: $this->description,
            category: $this->category,
            ruleData: $this->ruleData,
            version: $this->version,
            isActive: false,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: CarbonImmutable::now(),
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            previousVersionUuid: $this->previousVersionUuid,
        );
    }

    public function isEffectiveAt(CarbonImmutable $date): bool
    {
        if (! $this->isActive) {
            return false;
        }

        if ($date->lt($this->effectiveFrom)) {
            return false;
        }

        if ($this->effectiveTo !== null && $date->gte($this->effectiveTo)) {
            return false;
        }

        return true;
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->ruleData[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'rule_data' => $this->ruleData,
            'version' => $this->version,
            'is_active' => $this->isActive,
            'effective_from' => $this->effectiveFrom->toDateTimeString(),
            'effective_to' => $this->effectiveTo?->toDateTimeString(),
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->toDateTimeString(),
            'previous_version_uuid' => $this->previousVersionUuid,
        ];
    }

    private static function getNextVersion(string $code, string $previousVersionUuid): string
    {
        // In production, this would query the repository to get the previous version
        // For now, we'll increment the patch version
        $parts = explode('.', $previousVersionUuid);
        $parts[2] = (int) $parts[2] + 1;
        return implode('.', $parts);
    }
}
