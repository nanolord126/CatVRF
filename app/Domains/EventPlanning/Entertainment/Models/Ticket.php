<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Ticket extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'entertainment_tickets';

        protected $fillable = [
            'uuid',
            'booking_id',
            'event_id',
            'tenant_id',
            'ticket_number',
            'qr_code',
            'seat_label',
            'is_validated',
            'validated_at',
            'correlation_id',
        ];

        protected $casts = [
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
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
                if (empty($model->ticket_number)) {
                    $model->ticket_number = 'ENT-' . strtoupper(Str::random(10));
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
         * Бронирование (Booking)
         */
        public function booking(): BelongsTo
        {
            return $this->belongsTo(Booking::class, 'booking_id');
        }

        /**
         * Событие (Event)
         */
        public function event(): BelongsTo
        {
            return $this->belongsTo(Event::class, 'event_id');
        }

        /* --- Методы Канона (Check-in logic) --- */

        /**
         * Проверка: использован ли билет
         */
        public function isValidated(): bool
        {
            return $this->is_validated;
        }

        /**
         * Отметить билет как проверенный (Check-in)
         */
        public function validate(): void
        {
            if ($this->is_validated) {
                return;
            }

            $this->is_validated = true;
            $this->validated_at = now();
            $this->save();
        }

        /**
         * Получение correlation_id
         */
        public function getCorrelationId(): string
        {
            return (string) $this->correlation_id;
        }
}
