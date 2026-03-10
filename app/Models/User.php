<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, StrictTenantIsolation, HasEcosystemTracing;

    /**
     * AI Behavioral Telemetry Relation
     */
    public function aiTelemetry()
    {
        return $this->hasMany(\App\Models\Common\AiUserTelemetry::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_code',
        'phone',
        'address',
        'geo_location',
        'hired_at',
        'fired_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'geo_location' => 'array',
            'hired_at' => 'date',
            'fired_at' => 'date',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function schedules()
    {
        return $this->hasMany(\modules\Staff\Models\StaffSchedule::class);
    }

    public function payrollConfig()
    {
        return $this->hasOne(EmployeePayrollConfig::class);
    }

    public function salarySlips()
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class, 'owner_id');
    }

    public function medicalCards()
    {
        return $this->hasMany(MedicalCard::class, 'patient_id')->where('patient_type', 'HUMAN');
    }

    /** Персональный чеклист здоровья и процедур */
    public function healthRecommendations()
    {
        return $this->hasMany(\App\Models\Common\HealthRecommendation::class, 'user_id');
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }
}







