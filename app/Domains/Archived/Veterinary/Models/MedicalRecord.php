<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalRecord extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'veterinary_medical_records';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'pet_id',


            'veterinarian_id',


            'appointment_id',


            'diagnosis',


            'treatment_plan',


            'prescribed_medication',


            'lab_results',


            'next_visit_at',


            'is_confidential',


            'tags',


            'correlation_id',


        ];


        protected $casts = [


            'is_confidential' => 'boolean',


            'next_visit_at' => 'datetime',


            'lab_results' => 'json',


            'tags' => 'json',


        ];


        /**


         * Boot logic


         */


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_scope', function (Builder $builder) {


                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {


                    $builder->where('veterinary_medical_records.tenant_id', tenant()->id);


                }


            });


            static::addGlobalScope('confidential_scope', function (Builder $builder) {


                // Simplified: logic to restrict view based on auth user


            });


            static::creating(function (Model $model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {


                    $model->tenant_id = $model->tenant_id ?? tenant()->id;


                }


            });


        }


        /**


         * Relations: Pet


         */


        public function pet(): BelongsTo


        {


            return $this->belongsTo(Pet::class, 'pet_id');


        }


        /**


         * Relations: Veterinarian


         */


        public function veterinarian(): BelongsTo


        {


            return $this->belongsTo(Veterinarian::class, 'veterinarian_id');


        }


        /**


         * Relations: Originating Appointment


         */


        public function appointment(): BelongsTo


        {


            return $this->belongsTo(VeterinaryAppointment::class, 'appointment_id');


        }
}
