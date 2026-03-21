<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель мастера красоты.
 * Production 2026.
 */
final class Master extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'masters';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'user_id',
        'full_name',
        'specialization',
        'experience_years',
        'rating',
        'review_count',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'specialization' => 'collection',
        'tags' => 'collection',
        'metadata' => 'json',
        'rating' => 'float',
        'experience_years' => 'integer',
        'review_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'user_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'master_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'master_id');
    }

    public function portfolio(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'master_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'master_id');
    }
}
