declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model для фотографии объекта.
 * Production 2026.
 */
final class PropertyImage extends Model
{
    use SoftDeletes;

    protected $table = 'property_images';
    protected $fillable = [
        'tenant_id', 'property_id', 'image_url', 'thumb_url', 'sort_order',
        'type', 'description', 'correlation_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
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
