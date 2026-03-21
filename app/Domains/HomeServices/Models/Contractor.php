<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Contractor extends Model
{
    protected $table = 'contractors';
    protected $fillable = ['tenant_id', 'user_id', 'company_name', 'description', 'address', 'geo_point', 'services', 'specializations', 'phone', 'website', 'hourly_rate', 'rating', 'review_count', 'job_count', 'completed_count', 'is_verified', 'is_active', 'correlation_id'];
    protected $hidden = [];
    protected $casts = ['services' => 'collection', 'specializations' => 'collection', 'hourly_rate' => 'float', 'rating' => 'float', 'is_verified' => 'boolean', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function serviceListings(): HasMany { return $this->hasMany(ServiceListing::class); }
    public function schedules(): HasMany { return $this->hasMany(ContractorSchedule::class); }
    public function jobs(): HasMany { return $this->hasMany(ServiceJob::class); }
    public function reviews(): HasMany { return $this->hasMany(ServiceReview::class); }
    public function earnings(): HasMany { return $this->hasMany(ContractorEarning::class); }
}
