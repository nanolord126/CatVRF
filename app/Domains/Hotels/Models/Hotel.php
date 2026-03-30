<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Hotel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'hotels';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'address',
            'geo_point',
            'stars',
            'is_active',
            'schedule_json',
            'rating',
            'review_count',
            'correlation_id',
            'tags',
        ];

        protected $hidden = ['deleted_at'];

        protected $casts = [
            'is_active' => 'boolean',
            'schedule_json' => 'json',
            'tags' => 'json',
            'stars' => 'integer',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
            });

            // Global scope tenant_id (КАНОН 2026)
            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (int) tenant('id'));
            });
        }

        public function rooms(): HasMany
        {
            return $this->hasMany(Room::class);
        }

        public function bookings(): HasMany
        {
            return $this->hasMany(Booking::class);
        }

        public function amenities(): BelongsToMany
        {
            return $this->belongsToMany(Amenity::class, 'hotel_amenity_pivot', 'hotel_id', 'amenity_id');
        }

        public function b2bContracts(): HasMany
        {
            return $this->hasMany(B2BContract::class);
        }
}
