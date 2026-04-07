<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessAppointment extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_appointments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'specialist_id',
            'service_id',
            'client_id',
            'datetime_start',
            'datetime_end',
            'status', // pending, confirmed, completed, cancelled
            'price',
            'payment_status', // unpaid, paid, refunded, hold
            'medical_notes',
            'correlation_id',
        ];

        protected $casts = [
            'datetime_start' => 'datetime',
            'datetime_end' => 'datetime',
            'price' => 'integer',
            'medical_notes' => 'json',
        ];

        /**
         * Boot the model with tenant scoping and record automation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'null');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relation with the wellness center.
         */
        public function center(): BelongsTo
        {
            return $this->belongsTo(WellnessCenter::class, 'center_id');
        }

        /**
         * Relation with the specialist.
         */
        public function specialist(): BelongsTo
        {
            return $this->belongsTo(WellnessSpecialist::class, 'specialist_id');
        }

        /**
         * Relation with the service.
         */
        public function service(): BelongsTo
        {
            return $this->belongsTo(WellnessService::class, 'service_id');
        }

        /**
         * Reviews associated with the appointment.
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(WellnessReview::class, 'appointment_id');
        }

        /**
         * Filter appointments by status.
         */
        public function scopePending(Builder $query): Builder
        {
            return $query->where('status', 'pending');
        }

        /**
         * Filter appointments by confirmed status.
         */
        public function scopeConfirmed(Builder $query): Builder
        {
            return $query->where('status', 'confirmed');
        }

        /**
         * Filter appointments by today's date.
         */
        public function scopeToday(Builder $query): Builder
        {
            return $query->whereDate('datetime_start', Carbon::now()->toDateString());
        }

        /**
         * Completed appointments filter.
         */
        public function scopeCompleted(Builder $query): Builder
        {
            return $query->where('status', 'completed');
        }
}
