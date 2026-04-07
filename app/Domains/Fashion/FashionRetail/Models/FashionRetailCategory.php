<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailCategory extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'fashion_retail_categories';

        protected $fillable = [
        'correlation_id',
            'uuid',
            'tenant_id',
            'name',
            'description',
            'parent_id',
            'icon_url',
            'image_url',
            'order',
            'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'order' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function parent(): BelongsTo
        {
            return $this->belongsTo(FashionRetailCategory::class, 'parent_id');
        }

        public function children()
        {
            return $this->hasMany(FashionRetailCategory::class, 'parent_id');
        }

        public function products()
        {
            return $this->hasMany(FashionRetailProduct::class, 'category_id');
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
