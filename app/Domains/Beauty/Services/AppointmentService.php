<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private WalletService $walletService,
            private ConsumableDeductionService $consumableService,
        ) {}

        /**
         * Создать запись (бронирование).
         */
        public function createAppointment(array $data, string $correlationId = null): Appointment
        {
            $correlationId ??= (string) Str::uuid();

            return DB::transaction(function () use ($data, $correlationId) {
                // 1. Fraud Check (Исправленная сигнатура)
                $this->fraudControl->check(
                    userId: (int) ($data['user_id'] ?? auth()->id() ?? 0),
                    operationType: 'create_appointment',
                    amount: (int) ($data['price'] ?? 0),
                    correlationId: $correlationId
                );

                // 2. Валидация доступности мастера
                $isAvailable = $this->checkAvailability(
                    (int) $data['master_id'],
                    Carbon::parse($data['datetime_start']),
                    Carbon::parse($data['datetime_end'])
                );

                if (!$isAvailable) {
                    throw new \Exception('Master is not available at the selected time.');
                }

                // 3. Создание записи
                $appointment = Appointment::create(array_merge($data, [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]));

                // 4. Reserve Consumables (Hold)
                $this->consumableService->reserveForAppointment($appointment);

                Log::channel('audit')->info('Beauty appointment created', [
                    'appointment_id' => $appointment->id,
                    'user_id' => $appointment->user_id,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        }

        /**
         * Подтвердить выполнение услуги и списать оплату через кошелек.
         */
        public function completeAppointment(Appointment $appointment, string $correlationId = null): void
        {
            $correlationId ??= $appointment->correlation_id ?? (string) Str::uuid();

            DB::transaction(function () use ($appointment, $correlationId) {
                $appointment->update([
                    'status' => 'completed',
                    'payment_status' => 'captured',
                ]);

                // Списание расходников (реальное)
                $this->consumableService->deductForAppointment($appointment);

                // Финансовая операция через Wallet (исправленная сигнатура debit)
                // Предполагается, что у пользователя есть кошелек (wallet_id получаем из модели пользователя/тенаната)
                // Для упрощения ищем кошелек тенанта или пользователя
                $walletId = $appointment->user->wallet->id ?? 0;
                if (!$walletId) {
                    throw new \Exception('User wallet not found');
                }

                $this->walletService->debit(
                    $walletId,
                    (int) $appointment->price,
                    'Service completion: ' . $appointment->uuid,
                    $correlationId
                );

                Log::channel('audit')->info('Beauty appointment completed', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Проверка доступности слота времени.
         */
        private function checkAvailability(int $masterId, Carbon $start, Carbon $end): bool
        {
            return !Appointment::where('master_id', $masterId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('datetime_start', [$start, $end])
                        ->orWhereBetween('datetime_end', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('datetime_start', '<=', $start)
                              ->where('datetime_end', '>=', $end);
                        });
                })
                ->exists();
        }
}
