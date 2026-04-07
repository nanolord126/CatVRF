<?php declare(strict_types=1);

namespace App\Domains\Pet\DTO;

/**
 * Class PetAppointmentDto
 *
 * Part of the Pet vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Pet\DTO
 */
final readonly class PetAppointmentDto
{

    public function __construct(
            public int $tenant_id,
            public int $pet_id,
            public int $clinic_id,
            public int $service_id,
            public ?int $veterinarian_id,
            public string $appointment_at,
            public int $price,
            private string $currency = 'RUB',
            private ?string $notes = null,
            private string $correlation_id = '') {
            if (empty($this->correlation_id)) {
                throw new \InvalidArgumentException('correlation_id is mandatory for PetAppointmentDto');
            }
        }

        /**
         * Создать DTO из массива данных (например, из Request)
         */
        public static function fromArray(array $data): self
        {
            return new self(
                tenant_id: (int)($data['tenant_id'] ?? 0),
                pet_id: (int)($data['pet_id'] ?? 0),
                clinic_id: (int)($data['clinic_id'] ?? 0),
                service_id: (int)($data['service_id'] ?? 0),
                veterinarian_id: isset($data['veterinarian_id']) ? (int)$data['veterinarian_id'] : null,
                appointment_at: (string)($data['appointment_at'] ?? now()->toDateTimeString()),
                price: (int)($data['price'] ?? 0),
                currency: (string)($data['currency'] ?? 'RUB'),
                notes: $data['notes'] ?? null,
                correlation_id: (string)($data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString()),
            );
        }

        public function toArray(): array
        {
            return [
                'tenant_id' => $this->tenant_id,
                'pet_id' => $this->pet_id,
                'clinic_id' => $this->clinic_id,
                'service_id' => $this->service_id,
                'veterinarian_id' => $this->veterinarian_id,
                'appointment_at' => $this->appointment_at,
                'price' => $this->price,
                'currency' => $this->currency,
                'notes' => $this->notes,
                'correlation_id' => $this->correlation_id,
            ];
        }
}
