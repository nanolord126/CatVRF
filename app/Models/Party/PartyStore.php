<?php declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PartyStore extends Model
{

        protected $table = 'party_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'address',
            'contact_info',
            'metadata',
            'rating',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'contact_info' => 'json',
            'metadata' => 'json',
            'tags' => 'json',
            'rating' => 'float',
            'is_active' => 'boolean',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });

            // Global scope for tenant isolation
            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relationship: Store products.
         */
        public function products(): HasMany
        {
            return $this->hasMany(PartyProduct::class, 'party_store_id');
        }

        /**
         * Relationship: Store categories.
         */
        public function categories(): HasMany
        {
            return $this->hasMany(PartyCategory::class, 'party_store_id');
        }

        /**
         * Relationship: Ownership tenant.
         */
        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        /**
         * Relationship: Store orders.
         */
        public function orders(): HasMany
        {
            return $this->hasMany(PartyOrder::class, 'party_store_id');
        }

        /**
         * Scoped query: Only active stores.
         */
        public function scopeActive($query)
        {
            return $query->where('is_active', true);
        }
}
