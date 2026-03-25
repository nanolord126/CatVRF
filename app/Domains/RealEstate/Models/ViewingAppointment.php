declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для просмотра объекта.
 * Production 2026.
 */
final class ViewingAppointment extends Model
{
    use SoftDeletes;

    protected $table = 'viewing_appointments';
    protected $fillable = [
        'tenant_id', 'property_id', 'client_id', 'agent_id', 'datetime', 'status',
        'notes', 'client_rating', 'client_feedback', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'client_rating' => 'integer',
        'tags' => AsCollection::class,
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(RealEstateAgent::class, 'agent_id');
    }
}
