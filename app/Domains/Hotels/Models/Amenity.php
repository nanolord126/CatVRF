<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Amenity extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'hotel_amenities';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'icon', // simple icons for UI
            'description',
            'cost',
            'is_active',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'is_active' => 'boolean',
            'cost' => 'integer',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (int) tenant('id'));
            });
        }

        public function hotels(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
        {
            return $this->belongsToMany(Hotel::class, 'hotel_amenity_map');
        }
}
