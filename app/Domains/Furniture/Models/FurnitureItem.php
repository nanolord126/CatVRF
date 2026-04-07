<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureItem extends Model
{
    use HasFactory;

    /**
         * Boot the model to handle automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            if (function_exists('tenant') && tenant()) {
                static::addGlobalScope('tenant_id', function ($builder) {
                    $builder->where('tenant_id', tenant()->id);
                });
            }
        }
    }
