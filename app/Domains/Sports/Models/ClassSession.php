<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ClassSession extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'classes';
    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'studio_id',
        'trainer_id',
        'name',
        'description',
        'type',
        'duration_minutes',
        'max_participants',
        'price',
        'level',
        'equipment',
        'is_active',
        'is_recurring',
        'recurrence_pattern',
        'starts_at',
        'ends_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'equipment' => AsCollection::class,
        'tags' => AsCollection::class,
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'class_id');
    }
}
