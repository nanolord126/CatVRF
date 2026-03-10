<?php

namespace App\Domains\Events\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'events';
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'organizer_id',
        'title',
        'description',
        'location',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'status',
        'max_attendees',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'organizer_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }
}
