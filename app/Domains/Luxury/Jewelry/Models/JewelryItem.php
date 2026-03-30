<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JewelryItem extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'jewelry_items';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'metal',
            'stone', 'certificate_code', 'weight_grams', 'description', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'price' => 'integer',
            'weight_grams' => 'float',
        ];

        public function booted(): void
        {
            $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
        }
}
