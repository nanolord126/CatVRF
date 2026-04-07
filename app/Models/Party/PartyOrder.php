<?php declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PartyOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

        protected $table = 'party_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'party_store_id',
            'user_id',
            'total_cents',
            'prepayment_cents',
            'currency',
            'status',
            'payment_status',
            'event_date',
            'items_json',
            'contact_info',
            'is_b2b',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'items_json' => 'json',
            'contact_info' => 'json',
            'tags' => 'json',
            'total_cents' => 'integer',
            'prepayment_cents' => 'integer',
            'is_b2b' => 'boolean',
            'event_date' => 'datetime',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relationship: Associated store.
         */
        public function store(): BelongsTo
        {
            return $this->belongsTo(PartyStore::class, 'party_store_id');
        }

        /**
         * Relationship: Associated user.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Transition order status.
         */
        public function updateStatus(string $status): void
        {
            $this->update(['status' => $status]);
        }
}
