<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\Models;

use BooksDomainTrait, SoftDeletes;
use BooksDomainTrait;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BooksDomainTrait extends Model
{

    use HasFactory;

    protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });

            // Global Scope for Isolation by Tenant (Canon 2026)
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }
    }
