<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerMask — Модель для хранения масок анонимизации клиентов
 * 
 * Хранит маппинг реальных ID клиентов на анонимные маски для CRM
 * Маски контекстно-зависимые: один клиент может иметь разные маски
 * в разных магазинах/вертикалях
 */
final class CustomerMask extends Model
{
    protected $table = 'customer_masks';
    
    protected $fillable = [
        'customer_id',
        'tenant_id',
        'vertical_id',
        'store_id',
        'mask_type',
        'mask_number',
        'full_mask',
        'is_active',
        'initialized_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'initialized_at' => 'datetime',
    ];

    public const MASK_TYPE_CAT = 'cat';      // Котик
    public const MASK_TYPE_KITTEN = 'kitten'; // Кошечка

    /**
     * Get the real customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Scope for active masks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific context
     */
    public function scopeForContext($query, ?int $tenantId, ?int $verticalId = null, ?int $storeId = null)
    {
        $query->where('tenant_id', $tenantId);
        
        if ($verticalId !== null) {
            $query->where('vertical_id', $verticalId);
        }
        
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }
        
        return $query;
    }
}
