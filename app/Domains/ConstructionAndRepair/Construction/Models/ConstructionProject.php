<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructionProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, BelongsToTenant, SoftDeletes;

        protected $table = 'const_projects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'client_id',
            'title',
            'description',
            'status',          // draft, planning, active, halted, completed
            'estimated_cost',
            'actual_cost',
            'deadline_at',
            'address',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'estimated_cost' => 'integer',
            'actual_cost' => 'integer',
            'deadline_at' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
                $model->status = $model->status ?? 'planning';
            });
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        public function materials(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(ConstructionMaterial::class, 'project_id');
        }
}
