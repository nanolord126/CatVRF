declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для риэлтора / агента недвижимости.
 * Production 2026.
 */
final class RealEstateAgent extends Model
{
    use SoftDeletes;

    protected $table = 'real_estate_agents';
    protected $fillable = [
        'tenant_id', 'user_id', 'license_number', 'license_valid_until', 'specialization',
        'rating', 'completed_deals', 'is_verified', 'is_active', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'license_valid_until' => 'datetime',
        'rating' => 'float',
        'completed_deals' => 'integer',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'tags' => AsCollection::class,
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function viewingAppointments(): HasMany
    {
        return $this->hasMany(ViewingAppointment::class, 'agent_id');
    }
}
