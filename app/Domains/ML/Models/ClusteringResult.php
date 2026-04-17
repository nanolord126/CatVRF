<?php declare(strict_types=1);

namespace App\Domains\ML\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class ClusteringResult extends Model
{
    protected $fillable = [
        'tenant_id',
        'cluster_id',
        'user_count',
        'cluster_features',
        'metadata',
    ];

    protected $casts = [
        'cluster_features' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
