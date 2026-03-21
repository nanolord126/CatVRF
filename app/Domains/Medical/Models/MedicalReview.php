<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalReview extends Model
{
    use SoftDeletes;

    protected $table = 'medical_reviews';

    protected $fillable = [
        'tenant_id',
        'doctor_id',
        'clinic_id',
        'reviewer_id',
        'appointment_id',
        'rating',
        'comment',
        'review_aspects',
        'verified_appointment',
        'helpful_count',
        'unhelpful_count',
        'status',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'review_aspects' => 'collection',
        'verified_appointment' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MedicalDoctor::class, 'doctor_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointment::class, 'appointment_id');
    }
}
