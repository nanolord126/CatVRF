<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use App\Traits\HasOptimizedMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Video\Domain\Traits\HasVideoTrait;

final class MasterModel extends Model
{
    use HasFactory, SoftDeletes;
    use HasOptimizedMedia;
    use HasVideoTrait;

    protected $table = 'beauty_masters';

    protected $fillable = [
        'venue_id',
        'user_id',
        'first_name',
        'last_name',
        'patronymic',
        'slug',
        'avatar',
        'bio',
        'specializations',
        'certifications',
        'experience_years',
        'rating',
        'total_reviews',
        'is_active',
        'is_mobile_master',
        'base_commission_rate',
        'working_preferences',
        'hired_at',
        'fired_at',
    ];

    protected $casts = [
        'specializations' => 'array',
        'certifications' => 'array',
        'experience_years' => 'integer',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'is_active' => 'boolean',
        'is_mobile_master' => 'boolean',
        'base_commission_rate' => 'decimal:2',
        'working_preferences' => 'array',
        'hired_at' => 'datetime',
        'fired_at' => 'datetime',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MasterScheduleModel::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name} {$this->patronymic}");
    }
}
