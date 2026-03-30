<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeterinaryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'veterinary_services';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'clinic_id',


            'name',


            'description',


            'duration_minutes',


            'price',


            'category',


            'consumables_json',


            'is_active',


            'tags',


            'correlation_id',


        ];


        protected $casts = [


            'duration_minutes' => 'integer',


            'price' => 'integer',


            'is_active' => 'boolean',


            'consumables_json' => 'json',


            'tags' => 'json',


        ];


        /**


         * Boot logic


         */


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_scope', function (Builder $builder) {


                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {


                    $builder->where('veterinary_services.tenant_id', tenant()->id);


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


         * Relations: Clinic


         */


        public function clinic(): BelongsTo


        {


            return $this->belongsTo(VeterinaryClinic::class, 'clinic_id');


        }


        /**


         * Relations: Appointments using this service


         */


        public function appointments(): HasMany


        {


            return $this->hasMany(VeterinaryAppointment::class, 'service_id');


        }


        /**


         * Price in format (e.g. 1500.00)


         */


        public function getPriceFormattedAttribute(): string


        {


            return number_format($this->price / 100, 2, '.', ' ');


        }
}
