<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\ToysKids\Models;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ToyProduct extends Model
{
    use HasFactory;

    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'toy_products';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'name', 'description', 'category', 'brand',
            'age_min_years', 'age_max_years', 'gender',
            'price', 'current_stock',
            'has_safety_certificate', 'safety_certificate_num',
            'gift_wrapping_available', 'photo_url', 'status', 'tags',
        ];
        protected $casts = [
            'price'                    => 'int',
            'current_stock'            => 'int',
            'age_min_years'            => 'int',
            'age_max_years'            => 'int',
            'has_safety_certificate'   => 'boolean',
            'gift_wrapping_available'  => 'boolean',
            'tags'                     => 'json',
        ];

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()?->id) {
                    $query->where('tenant_id', tenant()?->id);
                }
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
