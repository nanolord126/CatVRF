<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $client_id
 * @property string|null $driver_id
 * @property string $status
 * @property array $pickup_location
 * @property array $dropoff_location
 * @property int|null $price
 * @property string $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Ride extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'taxi_rides';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'client_id',
        'driver_id',
        'status',
        'pickup_location',
        'dropoff_location',
        'price',
        'correlation_id',
    ];

    protected $casts = [
        'pickup_location' => 'json',
        'dropoff_location' => 'json',
        'price' => 'integer',
    ];


    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
