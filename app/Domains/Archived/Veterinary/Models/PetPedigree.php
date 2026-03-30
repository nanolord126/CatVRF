<?php declare(strict_types=1);

namespace App\Domains\Archived\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetPedigree extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pet_pedigrees';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'pet_id',


            'registration_number',


            'breed_club',


            'father_name',


            'father_reg_number',


            'mother_name',


            'mother_reg_number',


            'ancestors_tree',


            'document_url',


            'correlation_id',


        ];


        protected $casts = [


            'ancestors_tree' => 'json',


        ];


        protected static function booted(): void


        {


            static::creating(function (PetPedigree $model) {


                $model->uuid = (string) Str::uuid();


                if (auth()->check() && !$model->tenant_id) {


                    $model->tenant_id = auth()->user()->tenant_id;


                }


            });


            static::addGlobalScope('tenant_id', function ($builder) {


                if (auth()->check()) {


                    $builder->where('tenant_id', auth()->user()->tenant_id);


                }


            });


        }


        public function pet(): BelongsTo


        {


            return $this->belongsTo(Pet::class);


        }
}
