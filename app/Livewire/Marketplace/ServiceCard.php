<?php

declare(strict_types=1);


namespace App\Livewire\Marketplace;

use Livewire\Component;
use Illuminate\View\View;

final /**
 * ServiceCard
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceCard extends Component
{
    public int $serviceId;
    public string $serviceName;
    public int $price;
    public float $rating;
    public string $providerName;
    public string $vertical;

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
