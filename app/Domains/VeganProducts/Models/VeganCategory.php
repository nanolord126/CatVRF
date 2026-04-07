<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;



use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
     * VeganCategory Model - Classification of Plant-Based Goods.
     */
final class VeganCategory extends Model
{
        protected $table = 'vegan_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'icon', 'correlation_id'];

        public function products(): HasMany { return $this->hasMany(VeganProduct::class, 'vegan_category_id'); }
    }
