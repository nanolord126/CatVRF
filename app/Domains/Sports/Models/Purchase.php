<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Purchase extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'purchases';
        protected $fillable = [
            'tenant_id',
            'studio_id',
            'membership_id',
            'buyer_id',
            'item_type',
            'item_name',
            'quantity',
            'unit_price',
            'subtotal',
            'commission_amount',
            'total_amount',
            'payment_status',
            'purchase_status',
            'purchased_at',
            'starts_at',
            'expires_at',
            'transaction_id',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => AsCollection::class,
            'unit_price' => 'float',
            'subtotal' => 'float',
            'commission_amount' => 'float',
            'total_amount' => 'float',
            'purchased_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                $query->where('tenant_id', tenant('id'));
            });
        }

        public function studio(): BelongsTo
        {
            return $this->belongsTo(Studio::class);
        }

        public function membership(): BelongsTo
        {
            return $this->belongsTo(Membership::class);
        }

        public function buyer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'buyer_id');
        }
}
