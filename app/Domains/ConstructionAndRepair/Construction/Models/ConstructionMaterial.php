<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
final class ConstructionMaterial extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant, SoftDeletes;

        protected $table = 'const_materials';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'project_id',
            'name',
            'sku',
            'quantity',
            'unit',            // m3, ton, kg, piece
            'unit_price_cents',
            'estimated_need',
            'actual_usage',
            'supplier_id',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'quantity' => 'float',
            'unit_price_cents' => 'integer',
            'estimated_need' => 'float',
            'actual_usage' => 'float',
            'tags' => 'json',
        ];

    

        public function project(): BelongsTo
        {
            return $this->belongsTo(ConstructionProject::class, 'project_id');
        }
}
