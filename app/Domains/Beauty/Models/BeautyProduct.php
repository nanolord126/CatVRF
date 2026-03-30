<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $table = 'beauty_products';

        protected $fillable = [
            'tenant_id',
            'salon_id',
            'name',
            'sku',
            'current_stock',
            'min_stock_threshold',
            'price',
            'consumable_type',
            'description',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $hidden = [];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
            'price' => 'integer',
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }

        /**
         * Check if product is legally sellable.
         */
        public function isSellable(): bool
        {
            if ($this->deleted_at !== null) return false;

            $compliance = app(\App\Services\Compliance\ComplianceRequirementService::class);

            return ! $compliance->isBlocked($this);
        }
}
