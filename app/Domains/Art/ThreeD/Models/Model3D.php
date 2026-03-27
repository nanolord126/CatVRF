<?php

declare(strict_types=1);

namespace App\Domains\Art\ThreeD\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsJson;
use Illuminate\Database\Eloquent\Builder;

final class Model3D extends Model
{
    use SoftDeletes;

    protected $table = 'models_3d';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'modelable_type',
        'modelable_id',
        'name',
        'description',
        'file_path',
        'model_type',
        'file_size',
        'hash',
        'metadata',
        'status',
        'rejection_reason',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'metadata' => AsJson::class,
        'tags' => AsJson::class,
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
        'correlation_id',
    ];

    /**
     * Глобальные scopes для изоляции тенанта
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $query): void {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::addGlobalScope('business_group', static function (Builder $query): void {
            if (auth()->check() && request()->has('business_group_id')) {
                $query->where('business_group_id', request('business_group_id'));
            }
        });
    }

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function modelable(): MorphTo
    {
        return $this->morphTo();
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(Model3DConfiguration::class, 'model_3d_id');
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByVertical(Builder $query, string $vertical): Builder
    {
        return $query->whereJsonContains('tags->vertical', $vertical);
    }

    public function scopeRecentlyUsed(Builder $query): Builder
    {
        return $query->where('download_count', '>', 0)
            ->orderByDesc('download_count')
            ->limit(10);
    }
}
