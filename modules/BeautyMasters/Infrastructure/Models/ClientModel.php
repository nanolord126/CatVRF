<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class ClientModel extends Model
{
    use HasFactory, SoftDeletes;
    use HasMediaTrait;

    protected $table = 'beauty_clients';

    protected $fillable = [
        'user_id',
        'venue_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'birth_date',
        'gender',
        'notes',
        'allergies',
        'preferences',
        'skin_type',
        'hair_type',
        'avatar',
        'is_vip',
        'allow_marketing',
        'first_visit_at',
        'last_visit_at',
        'total_visits',
        'total_spent',
        'average_check',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'allergies' => 'array',
        'preferences' => 'array',
        'skin_type' => 'array',
        'hair_type' => 'array',
        'is_vip' => 'boolean',
        'allow_marketing' => 'boolean',
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'total_visits' => 'integer',
        'total_spent' => 'decimal:2',
        'average_check' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class);
    }

    public function loyaltyProfile(): HasMany
    {
        return $this->hasMany(LoyaltyProfileModel::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AppointmentPhotoModel::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
