<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Booking extends Model
{


        protected $table = 'travel_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'bookable_type',
            'bookable_id',
            'slots_count',
            'total_price',
            'status',
            'payment_status',
            'idempotency_key',
            'correlation_id',
            'metadata'
        ];

        protected $casts = [
            'total_price' => 'integer',
            'slots_count' => 'integer',
            'metadata' => 'json',
            'deleted_at' => 'datetime'
        ];

        protected static function booted(): void
        {
            static::creating(function (Booking $model) {
                if (!$model->uuid) $model->uuid = (string) Str::uuid();
                if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
                if (!$model->correlation_id) $model->correlation_id = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }

        public function bookable(): MorphTo
        {
            return $this->morphTo();
        }

        public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(Review::class);
        }

        
}
