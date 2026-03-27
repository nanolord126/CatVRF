<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final /**
 * CalculatorFormula
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CalculatorFormula extends Model
{
    protected $table = 'calculator_formulas';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'type',
        'formula_data',
        'correlation_id',
    ];

    protected $casts = [
        'formula_data' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (CalculatorFormula $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }
}
