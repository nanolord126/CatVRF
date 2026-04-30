<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Fitness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Fitness Booking — Запись на тренировку в вертикали Фитнес
 * 
 * Расширяет базовую Deal модель специфичными полями для фитнес-клубов и студий.
 */
final class FitnessBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'membership_id',
        'venue_id',
        'trainer_id',
        'workout_type_id',
        'schedule_slot_id',
        'booking_type',
        'workout_name',
        'workout_intensity',
        'is_group_workout',
        'booking_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'check_in_time',
        'check_out_time',
        'attendance_status',
        'cancellation_reason',
        'no_show_reason',
        'price',
        'discount_percent',
        'discount_amount',
        'final_price',
        'payment_status',
        'payment_method',
        'is_paid',
        'reminder_sent',
        'follow_up_sent',
        'trainer_notes',
        'client_notes',
        'performance_metrics',
        'calories_burned',
        'heart_rate_avg',
        'heart_rate_max',
        'session_rating',
        'session_feedback',
        'is_trial',
        'is_corporate',
        'corporate_company_id',
        'special_program_type',
        'special_program_enrollment_id',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'duration_minutes' => 'integer',
        'is_group_workout' => 'boolean',
        'reminder_sent' => 'boolean',
        'follow_up_sent' => 'boolean',
        'is_paid' => 'boolean',
        'is_trial' => 'boolean',
        'is_corporate' => 'boolean',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'performance_metrics' => 'json',
        'calories_burned' => 'integer',
        'heart_rate_avg' => 'integer',
        'heart_rate_max' => 'integer',
        'session_rating' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'crm_fitness_bookings';

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

    public function scopeByMembership($query, int $membershipId)
    {
        return $query->where('membership_id', $membershipId);
    }

    public function scopeByVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeByTrainer($query, int $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('booking_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopePersonalTraining($query)
    {
        return $query->where('booking_type', 'personal_training');
    }

    public function scopeGroupWorkout($query)
    {
        return $query->where('is_group_workout', true);
    }

    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    public function scopeCorporate($query)
    {
        return $query->where('is_corporate', true);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeNoShow($query)
    {
        return $query->where('attendance_status', 'no_show');
    }

    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
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
        $this->attendance_status = 'present';
        return $this->save();
    }

    public function checkOut(array $metrics = []): bool
    {
        $this->status = 'completed';
        $this->check_out_time = now();

        if (isset($metrics['calories_burned'])) {
            $this->calories_burned = $metrics['calories_burned'];
        }
        if (isset($metrics['heart_rate_avg'])) {
            $this->heart_rate_avg = $metrics['heart_rate_avg'];
        }
        if (isset($metrics['heart_rate_max'])) {
            $this->heart_rate_max = $metrics['heart_rate_max'];
        }
        if (isset($metrics['performance_metrics'])) {
            $this->performance_metrics = $metrics['performance_metrics'];
        }
        if (isset($metrics['trainer_notes'])) {
            $this->trainer_notes = $metrics['trainer_notes'];
        }

        return $this->save();
    }

    public function complete(array $data = []): bool
    {
        $this->status = 'completed';
        $this->check_out_time = now();

        if (isset($data['session_rating'])) {
            $this->session_rating = $data['session_rating'];
        }
        if (isset($data['session_feedback'])) {
            $this->session_feedback = $data['session_feedback'];
        }

        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function markNoShow(string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->attendance_status = 'no_show';
        $this->no_show_reason = $reason;
        return $this->save();
    }

    public function markLate(): bool
    {
        $this->attendance_status = 'late';
        return $this->save();
    }

    public function isPersonalTraining(): bool
    {
        return $this->booking_type === 'personal_training';
    }

    public function isGroupWorkout(): bool
    {
        return $this->is_group_workout;
    }

    public function isTrial(): bool
    {
        return $this->is_trial;
    }

    public function isCorporate(): bool
    {
        return $this->is_corporate;
    }

    public function getDuration(): ?int
    {
        if ($this->start_time === null || $this->end_time === null) {
            return $this->duration_minutes;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function getActualDuration(): ?int
    {
        if ($this->check_in_time === null || $this->check_out_time === null) {
            return null;
        }

        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет final_price
            if ($model->final_price === 0 && $model->price > 0) {
                $discount = $model->price * ($model->discount_percent / 100);
                $model->final_price = $model->price - $discount - $model->discount_amount;
            }
        });
    }
}
