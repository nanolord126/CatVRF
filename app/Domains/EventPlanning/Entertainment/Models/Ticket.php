<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Ticket extends Model
{
    use HasFactory;

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
            $this->validated_at = Carbon::now();
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
