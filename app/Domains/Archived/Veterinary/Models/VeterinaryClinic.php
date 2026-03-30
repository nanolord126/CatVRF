<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeterinaryClinic extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;


        protected $table = 'veterinary_clinics';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'business_group_id',


            'name',


            'address',


            'geo_point',


            'schedule_json',


            'rating',


            'review_count',


            'is_verified',


            'has_emergency',


            'tags',


            'correlation_id',


        ];


        protected $casts = [


            'schedule_json' => 'json',


            'tags' => 'json',


            'is_verified' => 'boolean',


            'has_emergency' => 'boolean',


            'rating' => 'float',


        ];


        protected $hidden = [


            'correlation_id',


        ];


        /**


         * Tenant Scoping Global Scope


         */


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_scope', function (Builder $builder) {


                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {


                    $builder->where('veterinary_clinics.tenant_id', tenant()->id);


                }


            });


            static::creating(function (Model $model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {


                    $model->tenant_id = $model->tenant_id ?? tenant()->id;


                }


            });


        }


        /**


         * Relations: Veterinarians working in the clinic


         */


        public function veterinarians(): HasMany


        {


            return $this->hasMany(Veterinarian::class, 'clinic_id');


        }


        /**


         * Relations: Services offered in the clinic


         */


        public function services(): HasMany


        {


            return $this->hasMany(VeterinaryService::class, 'clinic_id');


        }


        /**


         * Relations: Appointments booked for this clinic


         */


        public function appointments(): HasMany


        {


            return $this->hasMany(VeterinaryAppointment::class, 'clinic_id');


        }


        /**


         * Relations: Consumables inventory


         */


        public function consumables(): HasMany


        {


            return $this->hasMany(VeterinaryConsumable::class, 'clinic_id');


        }


        /**


         * Relations: Business Group (Affiliate)


         */


        public function businessGroup(): BelongsTo


        {


            return $this->belongsTo('App\Models\BusinessGroup', 'business_group_id');


        }
}
