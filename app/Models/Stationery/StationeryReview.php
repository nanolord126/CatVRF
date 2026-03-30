<?php declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'stationery_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'reviewable_id',
            'reviewable_type',
            'user_id',
            'rating',
            'comment',
            'photos',
            'correlation_id'
        ];

        protected $casts = [
            'photos' => 'json',
            'rating' => 'integer',
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

        public function reviewable(): MorphTo
        {
            return $this->morphTo();
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
