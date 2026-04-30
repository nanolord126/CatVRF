<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class IoTTelemetry
{
    public function __construct(
        public int $id,
        public int $iotDeviceId,
        public string $metricType,
        public ?float $value,
        public ?string $valueString,
        public ?array $valueJson,
        public ?string $unit,
        public bool $isAlert,
        public ?string $alertMessage,
        public CarbonImmutable $recordedAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function create(
        int $iotDeviceId,
        string $metricType,
        ?float $value = null,
        ?string $valueString = null,
        ?array $valueJson = null,
        ?string $unit = null,
        ?CarbonImmutable $recordedAt = null,
    ): self {
        return new self(
            id: 0,
            iotDeviceId: $iotDeviceId,
            metricType: $metricType,
            value: $value,
            valueString: $valueString,
            valueJson: $valueJson,
            unit: $unit,
            isAlert: false,
            alertMessage: null,
            recordedAt: $recordedAt ?? CarbonImmutable::now(),
            createdAt: CarbonImmutable::now(),
        );
    }

    public function asAlert(string $alertMessage, string $alertLevel = 'warning'): self
    {
        return new self(
            id: $this->id,
            iotDeviceId: $this->iotDeviceId,
            metricType: $this->metricType,
            value: $this->value,
            valueString: $this->valueString,
            valueJson: $this->valueJson,
            unit: $this->unit,
            isAlert: true,
            alertMessage: $alertMessage,
            alertLevel: $alertLevel,
            recordedAt: $this->recordedAt,
            createdAt: $this->createdAt,
        );
    }

    public function getNumericValue(): ?float
    {
        return $this->value;
    }

    public function getStringValue(): ?string
    {
        return $this->valueString;
    }

    public function getJsonValue(): ?array
    {
        return $this->valueJson;
    }
}
