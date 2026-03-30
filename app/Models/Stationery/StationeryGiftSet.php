<?php declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryGiftSet extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                if (auth()->check() && empty($model->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
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
