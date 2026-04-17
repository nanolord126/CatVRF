<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Tour extends Model
{


        protected $table = 'tours';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'destination_id',
            'title',
            'content',
            'base_price',
            'duration_days',
            'difficulty',
            'amenities',
            'tags',
            'is_active',
            'correlation_id'
        ];

        protected $casts = [
            'amenities' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'base_price' => 'integer',
            'deleted_at' => 'datetime'
        ];

        protected static function booted(): void
        {
            static::creating(function (Tour $model) {
                if (!$model->uuid) $model->uuid = (string) Str::uuid();
                if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
                if (!$model->correlation_id) $model->correlation_id = $this->request->header('X-Correlation-ID');
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        public function destination(): BelongsTo
        {
            return $this->belongsTo(Destination::class);
        }

        public function trips(): HasMany
        {
            return $this->hasMany(Trip::class);
        }

        
}
