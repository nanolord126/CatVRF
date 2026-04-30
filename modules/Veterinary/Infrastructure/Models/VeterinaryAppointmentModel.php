<?php

declare(strict_types=1);

namespace Modules\Veterinary\Infrastructure\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Modules\Veterinary\Domain\Entities\VeterinaryAppointment;
use Carbon\CarbonImmutable;

class VeterinaryAppointmentModel extends Model
{
    use TenantScoped;

    protected $table = 'veterinary_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'veterinarian_id',
        'pet_id',
        'service_id',
        'client_id',
        'appointment_at',
        'status',
        'final_price',
        'payment_status',
        'symptoms',
        'cancellation_reason',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'appointment_at' => 'datetime',
        'final_price' => 'integer',
        'tags' => 'json',
        'symptoms' => \App\Casts\AES256EncryptedCast::class,
    ];

    public function toDomain(): VeterinaryAppointment
    {
        return new VeterinaryAppointment(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            clinicId: $this->clinic_id,
            veterinarianId: $this->veterinarian_id,
            petId: $this->pet_id,
            serviceId: $this->service_id,
            clientId: $this->client_id,
            appointmentAt: CarbonImmutable::parse($this->appointment_at),
            status: $this->status,
            finalPrice: $this->final_price,
            paymentStatus: $this->payment_status,
            symptoms: $this->symptoms,
            cancellationReason: $this->cancellation_reason,
            tags: $this->tags,
            correlationId: $this->correlation_id,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
        );
    }
}
