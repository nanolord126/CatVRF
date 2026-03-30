<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetMedicalRecord extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;
        use SoftDeletes;

        protected $table = 'pet_medical_records';

        protected $fillable = [
            'tenant_id',
            'clinic_id',
            'vet_id',
            'owner_id',
            'pet_name',
            'record_type',
            'diagnosis',
            'treatment',
            'medications',
            'attachments',
            'recorded_at',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'medications' => 'collection',
            'attachments' => 'collection',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

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
}
