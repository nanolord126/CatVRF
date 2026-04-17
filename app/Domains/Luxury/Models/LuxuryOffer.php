<?php declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LuxuryOffer extends Model
{


        protected $table = 'luxury_exclusive_offers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'offerable_type',
            'offerable_id',
            'name',
            'description',
            'discount_kopecks',
            'special_price_kopecks',
            'valid_from',
            'valid_until',
            'is_public',
            'target_vip_levels',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'target_vip_levels' => 'json',
            'tags' => 'json',
            'is_public' => 'boolean',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('luxury_exclusive_offers.tenant_id', tenant()->id);
                }
            });
        }

        public function offerable(): MorphTo
        {
            return $this->morphTo();
        }
}
