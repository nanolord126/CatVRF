<?php

declare(strict_types=1);

namespace App\Domains\Food\Application\B2B\DataTransferObjects;

use App\Shared\Domain\ValueObjects\Address;
use App\Shared\Domain\ValueObjects\Contact;
use App\Shared\Domain\ValueObjects\Schedule;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;

final readonly class RestaurantDto
{
    public function __construct(
        public Uuid $id,
        public TenantId $tenantId,
        public string $name,
        public string $description,
        public Address $address,
        public Contact $contact,
        public Schedule $schedule,
        private ?Uuid $correlationId = null
    ) {

    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: new Uuid($data['id']),
            tenantId: new TenantId($data['tenant_id']),
            name: $data['name'],
            description: $data['description'],
            address: new Address(
                street: $data['address']['street'],
                city: $data['address']['city'],
                postalCode: $data['address']['postal_code'],
                country: $data['address']['country']
            ),
            contact: new Contact(
                phone: $data['contact']['phone'],
                email: $data['contact']['email']
            ),
            schedule: new Schedule($data['schedule']),
            correlationId: isset($data['correlation_id']) ? new Uuid($data['correlation_id']) : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'tenant_id' => $this->tenantId->toString(),
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address->toArray(),
            'contact' => $this->contact->toArray(),
            'schedule' => $this->schedule->toArray(),
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }
}
