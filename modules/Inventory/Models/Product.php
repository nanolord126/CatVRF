<?php declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use HasFactory, SoftDeletes;
    
        protected $table = 'inventory_products';
    
        protected $fillable = [
            'tenant_id',
            'business_group_id',
            'uuid',
            'sku',
            'name',
            'description',
            'unit',
            'current_stock',
            'hold_stock',
            'min_stock_threshold',
            'max_stock_threshold',
            'category',
            'price_kopeki',
            'is_consumable',
            'is_active',
            'correlation_id',
            'tags',
            'metadata',
        ];
    
        protected $casts = [
            'current_stock' => 'integer',
            'hold_stock' => 'integer',
            'min_stock_threshold' => 'integer',
            'max_stock_threshold' => 'integer',
            'price_kopeki' => 'integer',
            'is_consumable' => 'boolean',
            'is_active' => 'boolean',
            'tags' => 'json',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Global scope для tenant scoping.
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoped', function ($query) {
                if ($tenantId = tenant('id')) {
                    $query->where('tenant_id', $tenantId);
                }
            });
        }
    
        /**
         * Получить все движения товара.
         */
        public function movements(): HasMany
        {
            return $this->hasMany(\Modules\Inventory\Models\StockMovement::class, 'product_id');
        }
    
        /**
         * Получить все инвентаризационные проверки товара.
         */
        public function inventoryChecks(): HasMany
        {
            return $this->hasMany(\Modules\Inventory\Models\InventoryCheckItem::class, 'product_id');
        }
    
        /**
         * Получить доступный остаток (текущий - зарезервированный).
         */
        public function getAvailableStock(): int
        {
            return $this->current_stock - $this->hold_stock;
        }
    
        /**
         * Получить цену в рублях.
         */
        public function getPriceInRubles(): float
        {
            return $this->price_kopeki / 100;
        }
    
        /**
         * Проверить, низкий ли остаток.
         */
        public function isLowStock(): bool
        {
            return $this->current_stock < $this->min_stock_threshold;
        }
    
        /**
         * Проверить, превышен ли максимум.
         */
        public function isOverStocked(): bool
        {
            return $this->current_stock > $this->max_stock_threshold;
        }
    
        /**
         * Получить процент использования от максимума.
         */
        public function getUsagePercentage(): float
        {
            if ($this->max_stock_threshold <= 0) {
                return 0.0;
            }
    
            return ($this->current_stock / $this->max_stock_threshold) * 100.0;
        }
}
