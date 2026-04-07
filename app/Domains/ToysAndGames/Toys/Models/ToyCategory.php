<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use ToysDomainTrait;

/**
     * ToyCategory Model (L1)
     * Taxonomy: Puzzles, Lego, Sensory, Boards.
     */
final class ToyCategory extends Model
{
        use ToysDomainTrait;
        protected $table = 'toy_categories';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'correlation_id'];

        public function toys(): HasMany
        {
            return $this->hasMany(Toy::class, 'category_id');
        }
    }
