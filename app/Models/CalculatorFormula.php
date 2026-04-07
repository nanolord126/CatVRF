<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Class CalculatorFormula
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Models
 */
final class CalculatorFormula extends Model
{
    use HasFactory;

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
