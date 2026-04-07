<?php declare(strict_types=1);

namespace App\Domains\Consulting\HR\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JobVacancy extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant, SoftDeletes;

        protected $table = 'hr_vacancies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'title',
            'description',
            'requirements', // jsonb: skills, experience
            'salary_min',
            'salary_max',
            'currency',
            'status',      // open, closed, draft, filled
            'location',
            'remote_allowed',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'requirements' => 'json',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'remote_allowed' => 'boolean',
            'tags' => 'json',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());
                $model->status = $model->status ?? 'draft';
                $model->currency = $model->currency ?? 'RUB';
            });
        }

        public function businessGroup(): BelongsTo
        {
            return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
        }

        public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(JobApplication::class, 'vacancy_id');
        }
}
