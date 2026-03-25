declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Проект тюнинга авто.
 * Production 2026.
 */
final class TuningProject extends Model
{
    use HasUuids;

    protected $table = 'tuning_projects';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'car_brand',
        'car_model',
        'project_name',
        'description',
        'status',
        'budget',
        'spent_amount',
        'start_date',
        'estimated_completion',
        'completion_date',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'budget' => 'integer',
        'spent_amount' => 'integer',
        'start_date' => 'datetime',
        'estimated_completion' => 'datetime',
        'completion_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function client()
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'client_id');
    }
}
