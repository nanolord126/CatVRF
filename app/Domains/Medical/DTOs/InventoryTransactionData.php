<?php declare(strict_types=1);

namespace App\Domains\Medical\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InventoryTransactionData extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param int $itemId ID расходника
         * @param int $tenantId Клиника
         * @param int $quantity Количество
         * @param string $type Тип: 'hold' (резерв), 'deduct' (списание), 'release' (отмена резерва), 'add' (пополнение)
         * @param string $sourceType Источник: 'appointment', 'order', 'manual'
         * @param int $sourceId ID источника
         * @param string $correlationId UUID транзакции
         * @param string $reason Причина
         * @param int|null $userId ID пользователя-инициатора
         */
        public function __construct(
            public int $itemId,
            public int $tenantId,
            public int $quantity,
            public string $type,
            public string $sourceType,
            public int $sourceId,
            public string $correlationId,
            public string $reason = '',
            public ?int $userId = null,
        ) {
        }

        /**
         * Создание DTO для резерва под запись к врачу
         *
         * @param int $itemId
         * @param int $tenantId
         * @param int $quantity
         * @param int $appointmentId
         * @param string $correlationId
         * @return self
         */
        public static function forAppointmentHold(
            int $itemId,
            int $tenantId,
            int $quantity,
            int $appointmentId,
            string $correlationId
        ): self {
            return new self(
                itemId: $itemId,
                tenantId: $tenantId,
                quantity: $quantity,
                type: 'hold',
                sourceType: 'appointment',
                sourceId: $appointmentId,
                correlationId: $correlationId,
                reason: "Hold for Appointment #{$appointmentId}",
                userId: auth()->id() ?? 0
            );
        }

        /**
         * Создание DTO для окончательного списания после приема
         *
         * @param int $itemId
         * @param int $tenantId
         * @param int $quantity
         * @param int $appointmentId
         * @param string $correlationId
         * @return self
         */
        public static function forAppointmentDeduct(
            int $itemId,
            int $tenantId,
            int $quantity,
            int $appointmentId,
            string $correlationId
        ): self {
            return new self(
                itemId: $itemId,
                tenantId: $tenantId,
                quantity: $quantity,
                type: 'deduct',
                sourceType: 'appointment',
                sourceId: $appointmentId,
                correlationId: $correlationId,
                reason: "Final deduction for Appointment #{$appointmentId}",
                userId: auth()->id() ?? 0
            );
        }

        /**
         * Проверка корректности типа транзакции
         *
         * @return bool
         */
        public function isValidType(): bool
        {
            return in_array($this->type, ['hold', 'deduct', 'release', 'add', 'adjust']);
        }

        /**
         * Преобразование в массив для сохранения в stock_movements
         *
         * @return array
         */
        public function toMovementArray(): array
        {
            return [
                'inventory_item_id' => $this->itemId,
                'type' => $this->type,
                'quantity' => $this->type === 'out' || $this->type === 'deduct' ? -abs($this->quantity) : abs($this->quantity),
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'correlation_id' => $this->correlationId,
                'reason' => $this->reason,
                'created_by' => $this->userId,
                'created_at' => now(),
            ];
        }
}
