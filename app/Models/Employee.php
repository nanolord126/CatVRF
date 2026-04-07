<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'uuid',
        'full_name',
        'position',
        'employment_type',
        'base_salary_kopecks',
        'additional_payments',
        'hire_date',
        'termination_date',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'additional_payments' => 'array',
        'tags'                => 'array',
        'is_active'           => 'boolean',
        'hire_date'           => 'date',
        'termination_date'    => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /** Базовый оклад в рублях (для отображения). */
    public function getBaseSalaryRublesAttribute(): float
    {
        return $this->base_salary_kopecks / 100;
    }
}
