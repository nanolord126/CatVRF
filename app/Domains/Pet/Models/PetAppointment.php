<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PetAppointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pet_appointments';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'vet_id',
        'owner_id',
        'service_id',
        'appointment_number',
        'pet_name',
        'pet_type',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'status',
        'payment_status',
        'price',
        'commission_amount',
        'notes',
        'transaction_id',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'price' => 'float',
        'commission_amount' => 'float',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id', 'transaction_id'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class);
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(PetVet::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(PetGroomingService::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PetReview::class, 'appointment_id');
    }
}
