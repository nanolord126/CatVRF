<?php
declare(strict_types=1);

namespace App\Domains\Construction\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $project_id
 * @property string $name
 * @property int $total_cost_kopeks
 */
final class ConstructionEstimate extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'construction_estimates';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'project_id',
        'name',
        'total_cost_kopeks',
        'status',
        'items_json',
        'correlation_id',
    ];

    protected $casts = [
        'items_json' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ConstructionProject::class, 'project_id');
    }
}
