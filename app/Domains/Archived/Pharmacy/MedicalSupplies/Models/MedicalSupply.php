<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\MedicalSupplies\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalSupply extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;


        protected $table = 'medical_supplies';


        protected $fillable = [


            'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'current_stock',


            'requires_prescription', 'description', 'correlation_id', 'tags',


        ];


        protected $casts = [


            'tags' => 'json',


            'price' => 'integer',


            'current_stock' => 'integer',


            'requires_prescription' => 'boolean',


        ];


        public function booted(): void


        {


            $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));


        }
}
