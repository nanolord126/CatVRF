<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WeddingVendor extends Model
{


        protected $table = 'wedding_vendors';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'category',
            'base_price',
            'currency',
            'portfolio_links',
            'equipment_list',
            'rating',
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'portfolio_links' => 'json',
            'equipment_list' => 'json',
            'tags' => 'json',
            'base_price' => 'integer',
            'rating' => 'integer',
            'is_verified' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('wedding_vendors.tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Relation: Bookings
         */
        public function bookings(): HasMany
        {
            return $this->morphMany(WeddingBooking::class, 'bookable');
        }

        /**
         * Relation: Reviews
         */
        public function reviews(): HasMany
        {
            return $this->morphMany(WeddingReview::class, 'reviewable');
        }
}
