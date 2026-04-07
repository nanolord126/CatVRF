<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;

use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
     * VeganRecipe Model - Educational content to boost product sales.
     */
final class VeganRecipe extends Model
{
        protected $table = 'vegan_recipes';
        protected $fillable = ['uuid', 'tenant_id', 'title', 'description', 'steps', 'cooking_time_minutes', 'difficulty', 'ingredient_ids', 'nutrition_total', 'correlation_id'];
        protected $casts = ['steps' => 'json', 'ingredient_ids' => 'json', 'nutrition_total' => 'json'];
    }
