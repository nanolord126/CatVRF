<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautyService;
use App\Models\AIConstruction;
use App\Services\InventoryService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\AvailabilityConflictException;
use App\Exceptions\FraudBlockedException;

/**
 * Сервис бронирования услуг в вертикали Beauty.
 * Интегрирован с AI Beauty Look Constructor.
 */
final readonly class AppointmentBookingService
{
    public function __construct(
        private InventoryService $inventory,
        private MasterAvailabilityService $availability,
        private FraudControlService $fraud,
        private AppointmentCancellationService $cancellation,
        private AppointmentRescheduleService $reschedule,
    ) {}

    /**
     * Перенос бронирования (Rescheduling).
     * 
     * @param Appointment $appointment
     * @param Carbon $newStartTime
     * @return Appointment
     */
    public function reschedule(Appointment $appointment, Carbon $newStartTime): Appointment
    {
        return DB::transaction(function () use ($appointment, $newStartTime) {
            $now = Carbon::now();
            
            // 1. Расчет комиссии за перенос
            $feeData = $this->reschedule->calculateRescheduleFee($appointment, $newStartTime, $now);

            if (!$feeData['is_allowed']) {
                throw new \InvalidArgumentException($feeData['reason']);
            }

            // 2. Проверка доступности нового времени
            $duration = (int)$appointment->datetime_start->diffInMinutes($appointment->datetime_end);
            $newEndAt = (clone $newStartTime)->addMinutes($duration);

            if (!$this->availability->isAvailable($appointment->master_id, $newStartTime, $newEndAt)) {
                throw new AvailabilityConflictException('Новое время уже занято мастером.');
            }

            // 3. Обновление записи
            $appointment->update([
                'datetime_start' => $newStartTime,
                'datetime_end' => $newEndAt,
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'rescheduled_at' => $now->toIso8601String(),
                    'reschedule_fee_percent' => $feeData['fee_percent'],
                    'reschedule_fee_amount' => $feeData['fee_amount'],
                    'original_start' => $appointment->datetime_start->toIso8601String(),
                ]),
            ]);

            Log::channel('audit')->info('Appointment rescheduled', [
                'appointment_id' => $appointment->id,
                'fee' => $feeData['fee_amount'],
                'new_start' => $newStartTime->toDateTimeString(),
                'correlation_id' => $appointment->correlation_id,
            ]);

            return $appointment;
        });
    }

    /**
     * Создать бронирование на основе AI Look.
     * 
     * @param AIConstruction $look Результат работы AI конструктора
     * @param int $userId ID пользователя (клиента)
     * @param int $masterId ID мастера
     * @param Carbon $date Дата записи
     * @param string $startTime Время начала (H:i)
     * @param string $policy "flexible", "strict" или "corporate"
     * @return Appointment
     */
    public function bookFromLook(
        AIConstruction $look, 
        int $userId, 
        int $masterId, 
        Carbon $date, 
        string $startTime,
        string $policy = 'flexible'
    ): Appointment {
        $correlationId = Str::uuid()->toString();
        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $startTime);
        
        // 1. Fraud Check
        $fraudScore = $this->fraud->check([
            'user_id' => $userId,
            'type' => 'appointment_booking',
            'correlation_id' => $correlationId,
        ]);

        if ($fraudScore > 0.8) {
            Log::channel('fraud_alert')->warning('Appointment blocked by fraud control', [
                'user_id' => $userId,
                'score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);
            throw new FraudBlockedException('Подозрение на мошенничество. Операция заблокирована.');
        }

        return DB::transaction(function () use ($look, $userId, $masterId, $startDateTime, $correlationId, $policy) {
            $master = Master::findOrFail($masterId);
            $lookData = $look->construction_data; // {items: [...], services: [...]}
            
            // Получаем основную услугу из Look (или первую доступную)
            $serviceData = $lookData['services'][0] ?? null;
            if (!$serviceData) {
                throw new \InvalidArgumentException('AI Look не содержит информации об услугах.');
            }

            $service = BeautyService::findOrFail($serviceData['service_id']);
            $endDateTime = (clone $startDateTime)->addMinutes($service->duration_minutes ?? 60);

            // 2. Проверка доступности мастера
            if (!$this->availability->isAvailable($masterId, $startDateTime, $endDateTime)) {
                throw new AvailabilityConflictException('Выбранное время уже занято.');
            }

            // 3. Резерв товаров из Look (InventoryService) на 20 минут
            $itemsToReserve = $lookData['items'] ?? [];
            foreach ($itemsToReserve as $item) {
                // В каноне 2026 резерв делается на 20 минут для корзины/брони
                $this->inventory->reserveStock(
                    itemId: (int)$item['product_id'],
                    quantity: (int)($item['quantity'] ?? 1),
                    sourceType: 'appointment_reservation',
                    sourceId: 0, // ID записи появится после создания
                );
            }

            // 4. Определение B2C/B2B режима (для цен и комиссий)
            $isB2B = request()->has('inn') && request()->has('business_card_id');
            $priceCents = (int)($serviceData['price'] ?? $service->price);

            // 5. Создание записи
            $appointment = Appointment::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant('id') ?? $master->tenant_id,
                'salon_id' => $master->salon_id,
                'master_id' => $masterId,
                'service_id' => $service->id,
                'client_id' => $userId,
                'datetime_start' => $startDateTime,
                'datetime_end' => $endDateTime,
                'price_cents' => $priceCents,
                'status' => Appointment::STATUS_PENDING,
                'cancellation_policy' => $policy,
                'payment_status' => 'unpaid',
                'correlation_id' => $correlationId,
                'tags' => ['ai_look_id' => $look->id, 'mode' => $isB2B ? 'B2B' : 'B2C'],
                'metadata' => [
                    'ai_look_uuid' => $look->uuid,
                    'reserved_items' => $itemsToReserve,
                    'is_b2b' => $isB2B,
                    'is_complex' => count($itemsToReserve) > 3, // Для расчета штрафа
                ],
            ]);

            // Обновляем sourceId в резервах
            foreach ($itemsToReserve as $item) {
                // В реальном сервисе InventoryService можно добавить метод обновления source_id
            }

            // 6. Audit Log
            Log::channel('audit')->info('Appointment booked from AI Look', [
                'appointment_id' => $appointment->id,
                'user_id' => $userId,
                'master_id' => $masterId,
                'correlation_id' => $correlationId,
                'price' => $priceCents,
            ]);

            // 7. Уведомления (Dispatch Job)
            // SendAppointmentNotifications::dispatch($appointment);

            return $appointment;
        });
    }

    /**
     * Правила отмены (Cancellation Policy 2026).
     * 
     * @param Appointment $appointment
     * @param string $reason
     * @return bool
     */
    public function cancel(Appointment $appointment, string $reason = ''): bool
    {
        return DB::transaction(function () use ($appointment, $reason) {
            $now = Carbon::now();
            
            // Расчет штрафов через специализированный сервис
            $penaltyData = $this->cancellation->calculateRefund($appointment, $now);

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'cancel_reason' => $reason,
                    'cancel_at' => $now->toIso8601String(),
                    'penalty_percent' => $penaltyData['penalty_percent'],
                    'penalty_amount' => $penaltyData['penalty_amount'],
                    'refund_amount' => $penaltyData['refund_amount'],
                ]),
            ]);

            // ... (Освобождение резервов и аудит)

            return true;
        });
    }
}

