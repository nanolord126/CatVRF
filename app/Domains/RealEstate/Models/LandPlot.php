declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для земельного участка.
 * Production 2026.
 */
final class LandPlot extends Model
{
    use SoftDeletes;

    protected $table = 'land_plots';
    protected $fillable = [
        'tenant_id', 'property_id', 'plot_area', 'cadastral_number', 'purpose',
        'utilities_connected', 'description', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'plot_area' => 'integer',
        'utilities_connected' => 'boolean',
        'tags' => AsCollection::class,
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
