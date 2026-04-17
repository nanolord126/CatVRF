<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class PetPedigree extends Model
{


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
                if (function_exists('tenant') && tenant() && !$model->tenant_id) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function pet(): BelongsTo
        {
            return $this->belongsTo(Pet::class);
        }
}
