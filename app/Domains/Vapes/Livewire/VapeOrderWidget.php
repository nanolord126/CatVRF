<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Livewire;

use Livewire\Component;
use App\Domains\Vapes\Services\VapeAgeVerificationService;
use App\Domains\Vapes\Services\VapeOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VapeOrderWidget — Glassmorphism UI 2026
 * 
 * Виджет оформления заказа в вейп-вертикали с обязательной проверкой 18+.
 * Поддержка TailWind + Glassmorphism.
 */
final class VapeOrderWidget extends Component
{
    public int $amountKopecks = 250000; // 2500 руб (POD-система + 2 жижи)
    public bool $isAgeVerified = false;
    public string $correlationId;
    public array $items = [];

    /**
     * Монтирование компонента.
     */
    public function mount(VapeAgeVerificationService $ageVerifier): void
    {
        $this->correlationId = (string) Str::uuid();
        $this->isAgeVerified = $ageVerifier->hasAValidVerification(auth()->id());

        // Имитируем корзину (2026: 1 продавец = 1 корзина)
        $this->items[] = [
            'product_id' => 1,
            'name' => 'POD-System X Pro',
            'type' => 'device',
            'qty' => 1,
            'price_kopecks' => 150000,
        ];

        $this->items[] = [
            'product_id' => 2,
            'name' => 'Liquid Salt Berry 20mg',
            'type' => 'liquid',
            'qty' => 2,
            'price_kopecks' => 50000,
        ];
    }

    /**
     * Оформить заказ.
     */
    public function submitOrder(VapeOrderService $orderService): void
    {
        Log::channel('audit')->info('Vape order widget: submit', [
            'user_id' => auth()->id(),
            'correlation_id' => $this->correlationId,
        ]);

        try {
            DB::transaction(function () use ($orderService) {
                
                $orderService->createOrder(
                    userId: auth()->id(),
                    params: [
                        'amount_kopecks' => $this->amountKopecks,
                        'items' => $this->items,
                    ],
                    correlationId: $this->correlationId,
                );

                // Оповещение об успехе
                $this->dispatch('vape-order-success', [
                    'message' => 'Vape Order created! Next steps: Marking Scan.',
                ]);
            });

        } catch (\Throwable $e) {
            
            Log::channel('audit')->error('Vape order widget submit failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->dispatch('vape-order-error', [
                'message' => 'Order error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Рендеринг вида (Glassmorphism + Tailwind).
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.vapes.order-widget');
    }
}
