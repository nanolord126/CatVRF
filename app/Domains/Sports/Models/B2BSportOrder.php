<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BSportOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'b2b_sport_orders';

        protected $fillable = [
            'uuid', 'tenant_id', 'b2b_sport_storefront_id', 'user_id', 'order_number',
            'company_contact_person', 'company_phone', 'items', 'total_amount',
            'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
        ];

        protected $casts = [
            'items' => 'json',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (auth()->check() && auth()->user()->tenant_id) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        public function storefront(): BelongsTo
        {
            return $this->belongsTo(B2BSportStorefront::class, 'b2b_sport_storefront_id');
        }
}
