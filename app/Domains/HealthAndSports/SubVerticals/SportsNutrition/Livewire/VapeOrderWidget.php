<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Livewire;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Livewire\Component;
use Illuminate\Database\DatabaseManager;
use Illuminate\View\View;

final class VapeOrderWidget extends Component
{
    private readonly int $amountKopecks = 250000; // 2500 руб (POD-система + 2 жижи)

    private readonly bool $isAgeVerified = false;

    private readonly string $correlationId;

    private readonly array $items = [];

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    /**
     * Монтирование компонента.
     */
    public function mount(VapeAgeVerificationService $ageVerifier): void
    {
        $this->correlationId = (string) Str::uuid();
        $this->isAgeVerified = $ageVerifier->hasAValidVerification($this->guard->id());

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
        $this->logger->$this->logger->info('Vape order widget: submit', [
            'user_id' => $this->guard->id(),
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($orderService) {

                $orderService->createOrder(
                    userId: $this->guard->id(),
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

            $this->logger->error('Vape order widget submit failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->dispatch('vape-order-error', [
                'message' => 'Order error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Рендеринг вида (Glassmorphism + Tailwind).
     */
    public function render(): View
    {
        return $this->viewFactory->make('livewire.vapes.order-widget');
    }
}
