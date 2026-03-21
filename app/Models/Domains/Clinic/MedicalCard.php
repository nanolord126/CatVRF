<?php

declare(strict_types=1);

namespace App\Models\Domains\Clinic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalCard extends Model
{
    use HasFactory;

    protected $table = 'medical_cards';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'blood_type',
        'allergies',
        'medical_history',
        'notes',
        'last_check_up',
    ];

    protected $casts = [
        'allergies' => 'array',
        'medical_history' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\MedicalCardFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
