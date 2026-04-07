<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Domains\Food\Domain\ValueObjects\RestaurantStatus;
use App\Shared\Domain\Entities\AggregateRoot;
use App\Shared\Domain\ValueObjects\Address;
use App\Shared\Domain\ValueObjects\Contact;
use App\Shared\Domain\ValueObjects\Schedule;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

final class Restaurant extends AggregateRoot
{
    /**
     * @param Uuid $id
     * @param TenantId $tenantId
     * @param string $name
     * @param string $description
     * @param Address $address
     * @param Contact $contact
     * @param RestaurantStatus $status
     * @param Schedule $schedule
     * @param Collection<MenuSection> $menuSections
     * @param float $rating
     * @param int $reviewCount
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        private readonly TenantId $tenantId,
        public string $name,
        public string $description,
        public Address $address,
        public Contact $contact,
        public RestaurantStatus $status,
        public Schedule $schedule,
        public Collection $menuSections,
        private float $rating = 0.0,
        private int $reviewCount = 0,
        private ?Uuid $correlationId = null
    ) {
        parent::__construct($id);
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
            status: RestaurantStatus::from($data['status']),
            schedule: new Schedule($data['schedule']),
            menuSections: collect($data['menu_sections'] ?? [])->map(
                fn (array $section) => MenuSection::fromArray($section)
            ),
            rating: $data['rating'] ?? 0.0,
            reviewCount: $data['review_count'] ?? 0,
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
            'status' => $this->status->value,
            'schedule' => $this->schedule->toArray(),
            'menu_sections' => $this->menuSections->map(fn (MenuSection $section) => $section->toArray())->all(),
            'rating' => $this->rating,
            'review_count' => $this->reviewCount,
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }

    public function open(): void
    {
        if ($this->status === RestaurantStatus::OPEN) {
            // Consider throwing an exception if already open
            return;
        }
        $this->status = RestaurantStatus::OPEN;
    }

    public function close(): void
    {
        if ($this->status === RestaurantStatus::CLOSED) {
            // Consider throwing an exception if already closed
            return;
        }
        $this->status = RestaurantStatus::CLOSED;
    }

    public function updateRating(float $newRating, int $totalReviews): void
    {
        $this->rating = $newRating;
        $this->reviewCount = $totalReviews;
    }

    public function addMenuSection(MenuSection $section): void
    {
        $this->menuSections->add($section);
    }
}
