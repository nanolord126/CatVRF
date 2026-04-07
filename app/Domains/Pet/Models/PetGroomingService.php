<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetGroomingService extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'pet_grooming_services';

        protected $fillable = [
            'tenant_id',
            'clinic_id',
            'name',
            'description',
            'pet_type',
            'duration_minutes',
            'price',
            'cost_price',
            'tags',
            'is_active',
            'rating',
            'review_count',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'tags' => 'collection',
            'price' => 'float',
            'cost_price' => 'float',
            'rating' => 'float',
            'duration_minutes' => 'integer',
            'review_count' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        public function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function clinic(): BelongsTo
        {
            return $this->belongsTo(PetClinic::class);
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(PetAppointment::class, 'service_id');
        }
}
