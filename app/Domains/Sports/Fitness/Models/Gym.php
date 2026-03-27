<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use App\Models\User;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Gym extends Model
{
    use SoftDeletes;

    protected $table = 'gyms';
    protected $fillable = ['tenant_id', 'business_group_id', 'name', 'description', 'address', 'geo_point', 'amenities', 'schedule', 'monthly_membership_price', 'annual_membership_price', 'rating', 'review_count', 'member_count', 'is_verified', 'is_active', 'correlation_id'];
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
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
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
