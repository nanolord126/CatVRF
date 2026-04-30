<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\MeatShops\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class MeatBoxSubscription extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'meat_box_subscriptions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'meat_shop_id',
        'user_id',
        'correlation_id',
        'name',
        'price_kopecks',
        'frequency',
        'meat_types',
        'total_weight_grams',
        'delivery_day',
        'is_active',
        'started_at',
        'ended_at',
        'tags',
    ];

    protected $casts = [
        'price_kopecks' => 'integer',
        'total_weight_grams' => 'integer',
        'is_active' => 'boolean',
        'meat_types' => 'json',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'tags' => 'json',
    ];

    public function meatShop()
    {
        return $this->belongsTo(MeatShop::class, 'meat_shop_id');
    }

    public function isActive(): bool
    {
        return $this->is_active && (! $this->ended_at || CarbonImmutable::now()->isBefore($this->ended_at));
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('meat_box_subscriptions.tenant_id', tenant()->id);
        });
    }
}
