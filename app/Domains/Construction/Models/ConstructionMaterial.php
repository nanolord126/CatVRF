<?php

declare(strict_types=1);

namespace App\Domains\Construction\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Материал/Ресурс для строительства
 */
final class ConstructionMaterial extends Model
{
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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ConstructionProject::class, 'project_id');
    }
}
