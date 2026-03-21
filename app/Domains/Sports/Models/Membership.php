<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Membership extends Model
{
    use SoftDeletes;

    protected $table = 'memberships';
    protected $fillable = [
        'tenant_id',
        'studio_id',
        'name',
        'description',
        'type',
        'duration_days',
        'price',
        'classes_per_month',
        'included_classes',
        'benefits',
        'allow_guests',
        'max_guests_per_visit',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'included_classes' => AsCollection::class,
        'benefits' => AsCollection::class,
        'tags' => AsCollection::class,
        'allow_guests' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
