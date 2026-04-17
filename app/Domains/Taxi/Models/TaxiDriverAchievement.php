<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaxiDriverAchievement extends Model
{
    use HasFactory;

    protected $table = 'taxi_driver_achievements';

    protected $fillable = [
        'driver_id',
        'tenant_id',
        'business_group_id',
        'achievement_code',
        'achievement_name',
        'achievement_description',
        'awarded_at',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'awarded_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, 'driver_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
