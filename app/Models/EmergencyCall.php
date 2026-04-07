<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * EmergencyCall — запись о вызове экстренных служб.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int         $id
 * @property int|null    $tenant_id
 * @property string|null $caller_name
 * @property string      $caller_phone
 * @property string|null $address
 * @property float|null  $lat
 * @property float|null  $lon
 * @property string      $category       fire|medical|accident|crime|other
 * @property string      $status         new|dispatched|on_scene|resolved|cancelled|false_call
 * @property string|null $assigned_unit
 * @property string|null $dispatcher_notes
 * @property string|null $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class EmergencyCall extends Model
{
    protected $table = 'emergency_calls';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'caller_name',
        'caller_phone',
        'address',
        'lat',
        'lon',
        'category',
        'status',
        'assigned_unit',
        'dispatcher_notes',
        'correlation_id',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', ['new', 'dispatched', 'on_scene']);
    }

    public function scopeNew($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'new');
    }
}
