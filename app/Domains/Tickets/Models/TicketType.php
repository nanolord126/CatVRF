<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class TicketType extends Model
{


        protected $table = 'ticket_types';

        protected $fillable = [
            'uuid', 'tenant_id', 'event_id', 'name',
            'description', 'price', 'quantity', 'sold_count',
            'max_per_order', 'is_active', 'settings',
            'tags', 'correlation_id'
        ];

        protected $casts = [
            'price' => 'integer',
            'quantity' => 'integer',
            'sold_count' => 'integer',
            'max_per_order' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'json',
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()?->id) {
                    $builder->where('tenant_id', tenant()?->id);
                }
            });

            static::creating(function ($model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()?->id;
                }
            });
        }

        

        /**
         * Эвент типа.
         */
        public function event(): BelongsTo
        {
            return $this->belongsTo(Event::class);
        }

        /**
         * Проданные билеты.
         */
        public function soldTickets(): HasMany
        {
            return $this->hasMany(Ticket::class);
        }

        /**
         * Осталось в продаже.
         */
        public function getAvailableQuantityAttribute(): int
        {
            return max(0, $this->quantity - $this->sold_count);
        }

        /**
         * Проверка возможности покупки.
         */
        public function canBuy(int $count): bool
        {
            if (!$this->is_active) return false;
            if ($count > $this->max_per_order) return false;
            return $this->available_quantity >= $count;
        }

        /**
         * Увеличение счетчика продаж.
         */
        public function incrementSold(int $count = 1): void
        {
            $this->increment('sold_count', $count);
        }

        /**
         * Возврат счетчика при отмене.
         */
        public function decrementSold(int $count = 1): void
        {
            $this->decrement('sold_count', $count);
        }
}
