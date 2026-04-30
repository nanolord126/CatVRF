<?php

declare(strict_types=1);

namespace App\Domains\Pet\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class PetReview extends Model
{
    use TenantScoped;

    protected $table = 'pet_reviews';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'vet_id',
        'reviewer_id',
        'appointment_id',
        'rating',
        'comment',
        'review_aspects',
        'verified_visit',
        'helpful_count',
        'unhelpful_count',
        'status',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'review_aspects' => 'collection',
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
        'verified_visit' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class);
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(PetVet::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(PetAppointment::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
