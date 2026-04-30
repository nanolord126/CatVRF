<?php

declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class PetPedigree extends Model
{
    use TenantScoped;

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

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    protected static function booted(): void
    {
        self::creating(function (PetPedigree $model) {
            $model->uuid = (string) Str::uuid();
            if (function_exists('tenant') && tenant() && ! $model->tenant_id) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
