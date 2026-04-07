<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

final class DeliveryOrder extends Model
{
    use HasFactory;

    use SoftDeletes, LogsActivity;

        protected $table = 'delivery_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'sender_id',
            'courier_id',
            'status',
            'package_type',
            'weight_kg',
            'recipient_name',
            'recipient_phone',
            'pickup_point',
            'dropoff_point',
            'price',
            'correlation_id',
            'metadata'
        ];

        protected $casts = [
            'metadata' => 'json',
            'weight_kg' => 'float',
            'price' => 'integer',
            'tenant_id' => 'integer',
            'sender_id' => 'integer',
            'courier_id' => 'integer'
        ];

        /**
         * Глобальный скоупинг тенанта.
         */
        protected static function booted(): void
        {
            static::creating(function (DeliveryOrder $order) {
                $order->uuid = $order->uuid ?? (string) Str::uuid();
                $order->tenant_id = $order->tenant_id ?? (tenant()->id ?? 1);
                $order->correlation_id = $order->correlation_id ?? $this->request->header('X-Correlation-ID');
            });

            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Настройка логов активности.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['status', 'courier_id', 'price'])
                ->logOnlyDirty()
                ->dontSubmitEmptyLogs()
                ->setLogName('delivery_events');
        }

        /**
         * Отношения.
         */
        public function sender(): BelongsTo
        {
            return $this->belongsTo(User::class, 'sender_id');
        }

        public function courier(): BelongsTo
        {
            return $this->belongsTo(Driver::class, 'courier_id');
        }

        /**
         * Форматирование цены (копейки -> рубли).
         */
        public function getFormattedPriceAttribute(): string
        {
            return number_format($this->price / 100, 2, '.', ' ') . ' ₽';
        }
}
