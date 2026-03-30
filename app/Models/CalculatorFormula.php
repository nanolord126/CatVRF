<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculatorFormula extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
