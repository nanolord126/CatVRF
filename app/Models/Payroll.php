<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Class Payroll
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
final class Payroll extends Model
{
    protected $table = 'payrolls';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'employee_id',
        'period_start',
        'period_end',
        'base_salary_kopecks',
        'bonuses_kopecks',
        'deductions_kopecks',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags'         => 'array',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /** Итого в рублях (computed в PHP для удобства, в БД — storedAs). */
    public function getTotalRublesAttribute(): float
    {
        return (int) $this->total_kopecks / 100;
    }
}
