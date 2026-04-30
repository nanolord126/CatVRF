<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RestaurantModel extends Model
{
    public $timestamps = true;

    protected $table = 'food_restaurants';

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
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
