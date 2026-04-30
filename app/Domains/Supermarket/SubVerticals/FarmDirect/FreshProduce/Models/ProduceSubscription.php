<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\FreshProduce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class ProduceSubscription extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'produce_subscriptions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'client_id',
        'box_id',
        'uuid',
        'correlation_id',
        'frequency',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'preferred_slot',
        'next_delivery_date',
        'total_deliveries',
        'price_per_box',
        'status',
        'paused_until',
        'tags',
        'meta',
    ];

    protected $hidden = [];

    protected $casts = [
        'price_per_box'       => 'integer',
        'total_deliveries'    => 'integer',
        'delivery_lat'        => 'float',
        'delivery_lng'        => 'float',
        'next_delivery_date'  => 'date',
        'paused_until'        => 'date',
        'tags'                => 'array',
        'meta'                => 'array',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(ProduceBox::class, 'box_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProduceOrder::class, 'subscription_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
