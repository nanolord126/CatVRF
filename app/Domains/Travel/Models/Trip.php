<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Trip extends Model
{


        protected $table = 'trips';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'tour_id',
            'start_at',
            'end_at',
            'price',
            'max_slots',
            'booked_slots',
            'status',
            'correlation_id'
        ];

        protected $casts = [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'price' => 'integer',
            'max_slots' => 'integer',
            'booked_slots' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (Trip $model) {
                if (!$model->uuid) $model->uuid = (string) Str::uuid();
                if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
                if (!$model->correlation_id) $model->correlation_id = $this->request->header('X-Correlation-ID');
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        public function tour(): BelongsTo
        {
            return $this->belongsTo(Tour::class);
        }

        

        public function isAvailable(int $slots = 1): bool
        {
            return ($this->booked_slots + $slots) <= $this->max_slots && $this->status === 'active';
        }
}
