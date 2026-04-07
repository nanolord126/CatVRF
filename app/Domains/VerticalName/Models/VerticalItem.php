<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VerticalItem — основная Eloquent-модель вертикали VerticalName.
 *
 * Tenant-aware, business_group_id scoping.
 * Global scopes обеспечивают полную изоляцию данных по tenant_id.
 *
 * CANON 2026 — Layer 1: Models.
 * Обязательные поля: uuid, correlation_id, tags (json), tenant_id, business_group_id.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property string      $name
 * @property string|null $description
 * @property string      $status
 * @property int         $price_kopecks
 * @property string|null $sku
 * @property string|null $category
 * @property float       $rating
 * @property int         $review_count
 * @property bool        $is_active
 * @property bool        $is_b2b_available
 * @property int         $stock_quantity
 * @property array|null  $tags
 * @property array|null  $metadata
 * @property string|null $correlation_id
 * @property string|null $image_url
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class VerticalItem extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'vertical_name_items';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'status',
        'price_kopecks',
        'sku',
        'category',
        'rating',
        'review_count',
        'is_active',
        'is_b2b_available',
        'stock_quantity',
        'tags',
        'metadata',
        'correlation_id',
        'image_url',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'is_b2b_available' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
        'price_kopecks' => 'integer',
        'stock_quantity' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Инициализация модели — глобальные скоупы для tenant isolation.
     *
     * CANON 2026: tenant_id scoping обязателен.
     * uuid и correlation_id генерируются автоматически при creating.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }

            if ($model->status === null) {
                $model->status = 'draft';
            }
        });
    }

    /**
     * Tenant, которому принадлежит этот item.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Tenant::class,
            'tenant_id',
        );
    }

    /**
     * Business Group (филиал), если привязан.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\BusinessGroup::class,
            'business_group_id',
        );
    }

    /**
     * Заказы, связанные с этим item.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(
            VerticalOrder::class,
            'vertical_item_id',
        );
    }

    /**
     * Отзывы на этот item.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            VerticalReview::class,
            'vertical_item_id',
        );
    }

    /**
     * Проверка доступности товара (наличие > 0 и активен).
     */
    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->stock_quantity > 0
            && $this->status === 'published';
    }

    /**
     * Получить цену в рублях (из копеек).
     */
    public function getPriceRublesAttribute(): float
    {
        return round($this->price_kopecks / 100, 2);
    }

    /**
     * Scope для B2B-доступных товаров.
     */
    public function scopeB2bAvailable($query): void
    {
        $query->where('is_b2b_available', true)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope для активных и опубликованных товаров (B2C).
     */
    public function scopePublished($query): void
    {
        $query->where('status', 'published')
            ->where('is_active', true);
    }
}
