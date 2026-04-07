<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailShop extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'fashion_retail_shops';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'description',
            'address',
            'phone',
            'email',
            'website',
            'owner_id',
            'categories',
            'logo_url',
            'rating',
            'review_count',
            'is_verified',
            'is_active',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'categories' => 'json',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'tags' => 'json',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function owner(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'owner_id');
        }

        public function products()
        {
            return $this->hasMany(FashionRetailProduct::class, 'shop_id');
        }

        public function orders()
        {
            return $this->hasMany(FashionRetailOrder::class, 'shop_id');
        }
}
