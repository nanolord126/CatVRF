<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель отзыва о мастере/услуге.
 * Production 2026.
 */
final class Review extends Model
{
    use HasUuids;

    protected $table = 'reviews';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'appointment_id',
        'author_id',
        'rating',
        'text',
        'photos',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'photos' => 'collection',
        'tags' => 'collection',
        'metadata' => 'json',
        'rating' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'author_id');
    }
}
