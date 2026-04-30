<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\VetGrooming;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Vet Appointment — Запись на прием в вертикали Ветеринария и Груминг
 * 
 * Расширяет базовую Deal модель специфичными полями для ветеринарных клиник и груминг-салонов.
 */
final class VetAppointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'pet_id',
        'appointment_type',
        'service_category',
        'specific_service',
        'veterinarian_id',
        'groomer_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'check_in_time',
        'start_time',
        'end_time',
        'weight',
        'temperature',
        'symptoms',
        'diagnosis',
        'treatment',
        'medications',
        'follow_up_date',
        'follow_up_notes',
        'vaccination_administered',
        'vaccination_type',
        'vaccination_batch',
        'grooming_services',
        'grooming_notes',
        'behavior_rating',
        'behavior_notes',
        'before_photos',
        'after_photos',
        'total_price',
        'discount_percent',
        'discount_amount',
        'final_price',
        'payment_status',
        'payment_method',
        'reminder_sent',
        'no_show',
        'cancellation_reason',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'appointment_time' => 'datetime',
        'check_in_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'datetime',
        'duration_minutes' => 'integer',
        'weight' => 'decimal:2',
        'temperature' => 'decimal:1',
        'vaccination_administered' => 'boolean',
        'reminder_sent' => 'boolean',
        'no_show' => 'boolean',
        'total_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'symptoms' => 'json',
        'diagnosis' => 'encrypted',
        'treatment' => 'encrypted',
        'medications' => 'encrypted',
        'grooming_services' => 'json',
        'before_photos' => 'json',
        'after_photos' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_vet_appointments';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByPet($query, int $petId)
    {
        return $query->where('pet_id', $petId);
    }

    public function scopeByVeterinarian($query, int $vetId)
    {
        return $query->where('veterinarian_id', $vetId);
    }

    public function scopeByGroomer($query, int $groomerId)
    {
        return $query->where('groomer_id', $groomerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('appointment_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeVeterinary($query)
    {
        return $query->where('service_category', 'veterinary');
    }

    public function scopeGrooming($query)
    {
        return $query->where('service_category', 'grooming');
    }

    public function scopeVaccination($query)
    {
        return $query->where('appointment_type', 'vaccination');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNoShow($query)
    {
        return $query->where('no_show', true);
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->status = 'confirmed';
        return $this->save();
    }

    public function checkIn(): bool
    {
        $this->status = 'checked_in';
        $this->check_in_time = now();
        return $this->save();
    }

    public function start(): bool
    {
        $this->status = 'in_progress';
        $this->start_time = now();
        return $this->save();
    }

    public function complete(array $data = []): bool
    {
        $this->status = 'completed';
        $this->end_time = now();

        if (isset($data['diagnosis'])) {
            $this->diagnosis = $data['diagnosis'];
        }
        if (isset($data['treatment'])) {
            $this->treatment = $data['treatment'];
        }
        if (isset($data['medications'])) {
            $this->medications = $data['medications'];
        }
        if (isset($data['weight'])) {
            $this->weight = $data['weight'];
        }
        if (isset($data['temperature'])) {
            $this->temperature = $data['temperature'];
        }
        if (isset($data['follow_up_date'])) {
            $this->follow_up_date = $data['follow_up_date'];
        }
        if (isset($data['follow_up_notes'])) {
            $this->follow_up_notes = $data['follow_up_notes'];
        }
        if (isset($data['grooming_services'])) {
            $this->grooming_services = $data['grooming_services'];
        }
        if (isset($data['grooming_notes'])) {
            $this->grooming_notes = $data['grooming_notes'];
        }
        if (isset($data['behavior_rating'])) {
            $this->behavior_rating = $data['behavior_rating'];
        }
        if (isset($data['behavior_notes'])) {
            $this->behavior_notes = $data['behavior_notes'];
        }
        if (isset($data['after_photos'])) {
            $this->after_photos = $data['after_photos'];
        }

        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        $this->no_show = true;
        return $this->save();
    }

    public function administerVaccination(string $vaccineType, string $batch): bool
    {
        $this->vaccination_administered = true;
        $this->vaccination_type = $vaccineType;
        $this->vaccination_batch = $batch;
        return $this->save();
    }

    public function isVeterinary(): bool
    {
        return $this->service_category === 'veterinary';
    }

    public function isGrooming(): bool
    {
        return $this->service_category === 'grooming';
    }

    public function isVaccination(): bool
    {
        return $this->appointment_type === 'vaccination';
    }

    public function getDuration(): ?int
    {
        if ($this->start_time === null || $this->end_time === null) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет final_price
            if ($model->final_price === 0 && $model->total_price > 0) {
                $discount = $model->total_price * ($model->discount_percent / 100);
                $model->final_price = $model->total_price - $discount - $model->discount_amount;
            }
        });
    }
}
