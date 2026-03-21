<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель услуги красоты.
 * Production 2026.
 */
final class BeautyService extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'beauty_services';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'consumables_json',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'consumables_json' => 'collection',
        'tags' => 'collection',
        'metadata' => 'json',
        'price' => 'integer',
        'duration_minutes' => 'integer',
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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }
}
