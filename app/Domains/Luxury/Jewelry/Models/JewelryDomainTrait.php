<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JewelryDomainTrait, SoftDeletes;
use JewelryDomainTrait;

final class JewelryDomainTrait extends Model
{
    use HasFactory;

    protected static function booted_disabled(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 0;
                }
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant')) {
                    $builder->where('tenant_id', tenant()->id ?? 0);
                }
            });
        }

        public function getRouteKeyName(): string
        {
            return 'uuid';
        }
    }
