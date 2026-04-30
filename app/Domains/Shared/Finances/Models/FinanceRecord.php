<?php

declare(strict_types=1);

namespace App\Domains\Finances\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent Model: финансовая запись.
 *
 * Tenant-scoped через global scope. Все запросы автоматически
 * фильтруются по tenant_id текущего тенанта.
 *
 * Обязательные поля: uuid, correlation_id, tenant_id, business_group_id, tags.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $status
 * @property string|null $type
 * @property int $amount
 * @property array|null $tags
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class FinanceRecord extends Model
{
    use TenantScoped;

    protected $table = 'finance_records';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'description',
        'type',
        'status',
        'amount',
        'currency',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags'      => 'json',
        'metadata'  => 'json',
        'amount'    => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Тенант-владелец записи.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Бизнес-группа (филиал) для B2B.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    /**
     * Пользователь-создатель записи.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: фильтрация по бизнес-группе (B2B).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForBusinessGroup($query, int $businessGroupId)
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    /**
     * Scope: фильтрация по статусу.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: фильтрация по типу записи.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function ($query): void {
            if (function_exists('tenant') && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }
}
