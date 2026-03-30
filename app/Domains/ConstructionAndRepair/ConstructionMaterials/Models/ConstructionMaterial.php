<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructionMaterial extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'construction_materials';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'current_stock',
            'unit_type', 'consumption_per_m2', 'description', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'price' => 'integer',
            'current_stock' => 'integer',
            'consumption_per_m2' => 'float',
        ];

        public function booted(): void
        {
            $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
        }
}
