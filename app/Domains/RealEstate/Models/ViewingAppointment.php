<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ViewingAppointment extends Model
{
    use HasFactory;

    protected $table = 'viewing_appointments';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'property_id',
        'client_id',
        'agent_id',
        'datetime',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function client(): BelongsTo
    {
        // Assuming a User model exists at App\Models\User
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function agent(): BelongsTo
    {
        // Assuming a User model for agents
        return $this->belongsTo(\App\Models\User::class, 'agent_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}