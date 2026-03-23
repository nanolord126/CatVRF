<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Medication extends Model
{
    use SoftDeletes;

    protected $table = 'medications';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'inn',
        'sku',
        'price',
        'requires_prescription',
        'stock_quantity',
        'instructions',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'instructions' => 'json',
        'tags' => 'json',
        'price' => 'integer',
        'stock_quantity' => 'integer'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id ?? 0);
        });

        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });
    }
}