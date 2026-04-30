<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\ToysAndGames\ToysKids\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ToyProduct extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()?->id) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
