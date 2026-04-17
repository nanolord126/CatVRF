<?php declare(strict_types=1);

namespace App\Models\Stationery;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class StationeryGiftSet extends Model
{

    protected $table = 'stationery_gift_sets';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'store_id',
            'name',
            'price_cents',
            'product_ids',
            'theme',
            'target_age_range',
            'is_seasonal',
            'correlation_id'
        ];

        protected $casts = [
            'product_ids' => 'json',
            'is_seasonal' => 'boolean',
            'price_cents' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if ($this->guard->check() && empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if ($this->guard->check()) {
                    $builder->where('tenant_id', $this->guard->user()->tenant_id);
                }
            });
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(StationeryStore::class, 'store_id');
        }

        public function reviews(): MorphMany
        {
            return $this->morphMany(StationeryReview::class, 'reviewable');
        }
}
