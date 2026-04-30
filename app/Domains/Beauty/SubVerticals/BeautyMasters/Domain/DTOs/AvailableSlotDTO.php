<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

final readonly class AvailableSlotDTO
{
    public function __construct(
        public int $masterId,
        public string $masterName,
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public int $serviceId,
        public string $serviceName,
        public float $price,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            masterId: $data['master_id'],
            masterName: $data['master_name'],
            startTime: new \DateTimeImmutable($data['start_time']),
            endTime: new \DateTimeImmutable($data['end_time']),
            serviceId: $data['service_id'],
            serviceName: $data['service_name'],
            price: (float) $data['price'],
        );
    }

    public function toArray(): array
    {
        return [
            'master_id' => $this->masterId,
            'master_name' => $this->masterName,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'price' => $this->price,
        ];
    }
}
