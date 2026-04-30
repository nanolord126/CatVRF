<?php

declare(strict_types=1);

namespace App\Domains\Sports\SportingGoods\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportProduct extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'sport_products';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'tags',
        'name', 'price',
    ];

    protected $casts = [
        'tags' => 'json',
        'price' => 'integer',
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
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
