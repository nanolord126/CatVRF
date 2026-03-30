<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Booking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'entertainment_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'event_id',
            'user_id',
            'type',
            'status',
            'total_amount_kopecks',
            'selected_seats',
            'idempotency_key',
            'correlation_id',
            'tags',
            'created_at',
            'updated_at',
        ];

        protected $casts = [
            'total_amount_kopecks' => 'integer',
            'selected_seats' => 'json',
            'tags' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
            'idempotency_key',
        ];

        /**
         * КАНОН: Инициализация модели, авто-генерация UUID и Global Scope
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /* --- Отношения (Relations) --- */

        /**
         * Связь с событием
         */
        public function event(): BelongsTo
        {
            return $this->belongsTo(Event::class, 'event_id');
        }

        /**
         * Билеты, привязанные к этому бронированию
         */
        public function tickets(): HasMany
        {
            return $this->hasMany(Ticket::class, 'booking_id');
        }

        /* --- Статус-методы (Status logic) --- */

        /**
         * Проверка: просрочено ли бронирование (20 минут на оплату/подтверждение)
         */
        public function isExpired(): bool
        {
            if ($this->status !== 'pending') {
                return false;
            }

            return $this->created_at->addMinutes(20)->isPast();
        }

        /**
         * Проверка: оплачено ли бронирование
         */
        public function isPaid(): bool
        {
            return $this->status === 'paid' || $this->status === 'confirmed';
        }

        /**
         * Перевод в статус Оплачено
         */
        public function markAsPaid(): void
        {
            $this->status = 'paid';
            $this->save();
        }

        /**
         * Перевод в статус Отменено
         */
        public function markAsCancelled(): void
        {
            $this->status = 'cancelled';
            $this->save();
        }

        /**
         * Проверка на B2B режим (корпоратив)
         */
        public function isB2B(): bool
        {
            return $this->type === 'b2b';
        }

        /**
         * Получить сумму в рублях
         */
        public function getTotalAmount(): float
        {
            return $this->total_amount_kopecks / 100;
        }

        /**
         * Получить список выбранных мест как массив
         */
        public function getSelectedSeatsArray(): array
        {
            return is_array($this->selected_seats) ? $this->selected_seats : [];
        }

        /**
         * Количество билетов в бронировании
         */
        public function getTicketCount(): int
        {
            return count($this->getSelectedSeatsArray());
        }

        /**
         * Получение correlation_id
         */
        public function getCorrelationId(): string
        {
            return (string) $this->correlation_id;
        }
}
