declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Услуга СТО (замена масла, шиномонтаж и т.д.).
 * Production 2026.
 */
final class AutoService extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'auto_services';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'required_parts',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'required_parts' => 'collection',
        'price' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(AutoServiceOrder::class, 'service_id');
    }
}
