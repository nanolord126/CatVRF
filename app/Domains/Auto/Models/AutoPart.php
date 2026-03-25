declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Запчасть для авто.
 * Production 2026.
 */
final class AutoPart extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'auto_parts';

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'brand',
        'current_stock',
        'min_stock_threshold',
        'price',
        'description',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'price' => 'integer',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }
}
