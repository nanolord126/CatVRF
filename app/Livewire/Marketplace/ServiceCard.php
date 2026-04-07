<?php declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Livewire\Component;

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
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Livewire\Marketplace
 */
final class ServiceCard extends Component
{
    private int $serviceId;
        private string $serviceName;
        private int $price;
        private float $rating;
        private string $providerName;
        private string $vertical;

        /**
         * Handle mount operation.
         *
         * @throws \DomainException
         */
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
            return view('livewire.marketplace.service-card');
        }
}
