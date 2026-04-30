<?php

declare(strict_types=1);

namespace App\Domains\Booking\Services;

use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Payment\PaymentServiceAdapter;
use App\Domains\Shared\FraudML\Services\FraudMLCoordinatorService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Booking.
 *
 * Координирует бронирования (отели, мастера и т.д.).
 */
final readonly class BookingService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly FraudMLCoordinatorService $fraudMLCoordinator,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подготовить бронирование.
     *
     * @param  array{service_id: int, start_date: string, end_date: string, location: string}  $bookingData
     * @return array{total_cost: int, availability: bool}
     */
    public function prepareBooking(array $bookingData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Booking preparation started', [
            'correlation_id' => $correlationId,
            'service_id' => $bookingData['service_id'] ?? null,
        ]);

        // Проверка доступности (упрощённо)
        $availability = $this->checkAvailability($bookingData);

        // Расчёт стоимости
        $totalCost = $this->calculateBookingCost($bookingData);

        return [
            'total_cost' => $totalCost,
            'availability' => $availability,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать бронирование.
     *
     * @param  array{user_id: int, service_id: int, start_date: string, end_date: string, location: string, amount: int}  $bookingData
     * @return array{booking_id: int, payment_intent_id: string}
     */
    public function createBooking(array $bookingData): array
    {
        $correlationId = (string) Str::uuid();

        $this->logger->info('Booking creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $bookingData['user_id'],
            'service_id' => $bookingData['service_id'] ?? null,
        ]);

        return $this->db->transaction(function () use ($bookingData, $correlationId) {
            // Fraud ML check
            $fraudResult = $this->fraudMLCoordinator->checkPaymentRisk([
                'amount' => $bookingData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $bookingData['user_id'],
                'vertical' => 'booking',
                'correlation_id' => $correlationId,
            ]);

            // Создание бронирования
            $bookingId = $this->db->table('bookings')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id' => $bookingData['user_id'],
                'service_id' => $bookingData['service_id'],
                'start_date' => $bookingData['start_date'],
                'end_date' => $bookingData['end_date'],
                'location' => $bookingData['location'],
                'status' => 'pending',
                'total_amount' => $bookingData['amount'] ?? 0,
                'fraud_score' => $fraudResult['score'] ?? 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            // Подготовка платежа
            $paymentPreparation = $this->paymentAdapter->preparePayment([
                'amount' => $bookingData['amount'] ?? 0,
                'currency' => 'RUB',
                'user_id' => $bookingData['user_id'],
                'vertical' => 'booking',
                'payable_type' => 'booking',
                'payable_id' => $bookingId,
            ]);

            return [
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentPreparation['payment_intent_id'],
                'client_secret' => $paymentPreparation['client_secret'],
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Проверить доступность.
     */
    private function checkAvailability(array $bookingData): bool
    {
        // Упрощённо - всегда доступно
        return true;
    }

    /**
     * Рассчитать стоимость бронирования.
     */
    private function calculateBookingCost(array $bookingData): int
    {
        $startDate = \Carbon\Carbon::parse($bookingData['start_date']);
        $endDate = \Carbon\Carbon::parse($bookingData['end_date']);
        $days = $startDate->diffInDays($endDate);
        
        return $days * 1000; // 1000 руб за день
    }
}
