<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Gym extends Model
{

    protected $table = 'gyms';
    protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'business_group_id', 'name', 'description', 'address', 'geo_point', 'amenities', 'schedule', 'monthly_membership_price', 'annual_membership_price', 'rating', 'review_count', 'member_count', 'is_verified', 'is_active', 'correlation_id'];
    protected $casts = [
        'geo_point' => 'geometry',
        'amenities' => 'collection',
        'schedule' => 'collection',
        'monthly_membership_price' => 'float',
        'annual_membership_price' => 'float',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    public function fitnessClasses(): HasMany
    {
        return $this->hasMany(FitnessClass::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(PerformanceMetric::class);
    }
}
