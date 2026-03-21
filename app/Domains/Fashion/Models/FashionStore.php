<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FashionStore extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'owner_id',
        'name',
        'description',
        'logo_url',
        'cover_image_url',
        'categories',
        'rating',
        'review_count',
        'product_count',
        'is_verified',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'categories' => 'collection',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            if (tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FashionProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FashionOrder::class);
    }
}
