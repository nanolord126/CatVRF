<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Carbon\Carbon;
use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * ElectronicsWarranty - Service assurance data.
     */
final class ElectronicsWarranty extends Model
{
        protected $table = 'electronics_warranties';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'product_id',
            'order_id',
            'user_id',
            'serial_number',
            'starts_at',
            'expires_at',
            'status',
            'terms',
            'correlation_id',
        ];

        protected $casts = [
            'starts_at' => 'date',
            'expires_at' => 'date',
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?: (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
        {
            return $this->belongsTo(ElectronicsProduct::class, 'product_id');
        }

        public function scopeIsActive(Builder $query): Builder
        {
            return $query->where('status', 'active')->where('expires_at', '>=', Carbon::now());
        }
    }
