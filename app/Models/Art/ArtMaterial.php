<?php declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ArtMaterial extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'art_materials';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'sku',
            'price_cents',
            'stock_level',
            'min_threshold',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'price_cents' => 'integer',
            'stock_level' => 'integer',
            'min_threshold' => 'integer',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (ArtMaterial $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }
}
