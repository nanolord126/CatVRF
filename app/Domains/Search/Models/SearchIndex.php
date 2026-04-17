<?php declare(strict_types=1);

namespace App\Domains\Search\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class SearchIndex extends Model
{
    protected $fillable = [
        'tenant_id',
        'searchable_type',
        'searchable_id',
        'title',
        'content',
        'metadata',
        'ranking_score',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ranking_score' => 'float',
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

    public function scopeByType($query, string $type)
    {
        return $query->where('searchable_type', $type);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }
}
