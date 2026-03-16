<?php

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_services';

    protected $fillable = [
        'salon_id',
        'tenant_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_active',
        'category',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
