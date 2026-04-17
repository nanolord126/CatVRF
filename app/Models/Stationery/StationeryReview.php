<?php declare(strict_types=1);

namespace App\Models\Stationery;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

final class StationeryReview extends Model
{

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

        public function reviewable(): MorphTo
        {
            return $this->morphTo();
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
