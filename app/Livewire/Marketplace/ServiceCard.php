<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use App\Services\AuditService;
use App\Services\FraudControlService;

/**
 * Class ServiceCard
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final class ServiceCard extends Component
{
    private readonly int $serviceId;

    private readonly string $serviceName;

    private readonly int $price;

    private readonly float $rating;

    private readonly string $providerName;

    private readonly string $vertical;

    /**
     * Handle mount operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $serviceId, string $serviceName, int $price, float $rating, string $providerName, string $vertical): void
    {
        $this->serviceId = $serviceId;
        $this->serviceName = $serviceName;
        $this->price = $price;
        $this->rating = $rating;
        $this->providerName = $providerName;
        $this->vertical = $vertical;
    }

    public function bookService(): void
    {
        session()->put('booking_service', [
            'service_id' => $this->serviceId,
            'name' => $this->serviceName,
            'price' => $this->price,
            'vertical' => $this->vertical,
            'provider' => $this->providerName,
        ]);

        $this->dispatch('service-booked', serviceId: $this->serviceId);
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.marketplace.service-card');
    }
}
