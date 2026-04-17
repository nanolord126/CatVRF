<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Jewelry3DModel extends Model
{

    protected $table = '3d_models';

        protected $fillable = [
            'uuid',
            'correlation_id',
            'tenant_id',
            'business_group_id',
            'jewelry_item_id',
            'model_url',
            'texture_url',
            'material_type',
            'dimensions',
            'weight_grams',
            'preview_image_url',
            'ar_compatible',
            'vr_compatible',
            'file_size_mb',
            'format',
            'status',
            'tags',
        ];

        protected $casts = [
            'dimensions' => 'json',
            'tags' => 'json',
            'ar_compatible' => 'boolean',
            'vr_compatible' => 'boolean',
        ];

        public function jewelry(): BelongsTo
        {
            return $this->belongsTo(JewelryItem::class, 'jewelry_item_id');
        }

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('tenant_id', tenant()->id);
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
