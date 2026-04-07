<?php

declare(strict_types=1);

namespace App\Domains\Food\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RestaurantModel extends Model
{
    use HasUuids;

    protected $table = 'food_restaurants';

    public $timestamps = true;

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'name',
        'description',
        'address',
        'contact',
        'status',
        'schedule',
        'rating',
        'review_count',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'address' => 'json',
        'contact' => 'json',
        'schedule' => 'json',
        'tags' => 'json',
        'rating' => 'float',
        'review_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}
