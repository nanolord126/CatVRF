<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель использованных расходников.
 * Production 2026.
 */
final class BeautyConsumable extends Model
{
    use HasUuids;

    protected $table = 'beauty_consumables';

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'product_id',
        'quantity_used',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'tags' => 'collection',
        'quantity_used' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BeautyProduct::class, 'product_id');
    }
}
