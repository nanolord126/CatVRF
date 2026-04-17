<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Инвентаризация (InventoryCheck).
 *
 * Плановая или внеплановая сверка остатков на складе.
 * Проводит InventoryAuditService, фиксирует расхождения.
 *
 * @property int         $id
 * @property int         $warehouse_id
 * @property int         $tenant_id
 * @property int         $employee_id
 * @property string      $uuid
 * @property string      $status
 * @property array|null  $discrepancies
 * @property string|null $comments
 * @property string|null $correlation_id
 * @property array|null  $tags
 */
final class InventoryCheck extends Model
{

    protected $table = 'inventory_checks';

    protected $fillable = [
        'warehouse_id',
        'tenant_id',
        'employee_id',
        'uuid',
        'status',
        'discrepancies',
        'comments',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'discrepancies' => 'json',
        'tags'          => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
