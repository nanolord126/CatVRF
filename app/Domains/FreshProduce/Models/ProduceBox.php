<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель бокса свежих продуктов (недельный/сезонный набор) — КАНОН 2026.
 */
final class ProduceBox extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'produce_boxes';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'contents',
        'price',
        'subscription_days',
        'weight_kg',
        'is_seasonal',
        'season_months',
        'photo_url',
        'status',
        'tags',
        'meta',
    ];

    protected $hidden = [];

    protected $casts = [
        'price'            => 'integer',
        'subscription_days'=> 'integer',
        'weight_kg'        => 'float',
        'is_seasonal'      => 'boolean',
        'contents'         => 'array',
        'season_months'    => 'array',
        'tags'             => 'array',
        'meta'             => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProduceSubscription::class, 'box_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProduceOrder::class)->whereNull('subscription_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
