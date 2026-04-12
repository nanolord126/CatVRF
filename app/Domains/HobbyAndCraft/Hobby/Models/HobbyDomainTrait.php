<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;
use Illuminate\Database\Eloquent\Model;



use Illuminate\Database\Eloquent\Factories\HasFactory;


final class HobbyDomainTrait extends Model
{
    use HasFactory;


    public static function bootHobbyDomainTrait(): void
        {
            static::creating(function (Model $model) {
                if (!$model->uuid) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (!$model->tenant_id && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('hobby_tenant_scope', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }
    }
