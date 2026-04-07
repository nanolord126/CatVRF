<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


/**
     * ToyStore Model (L1)
     * Represents a logical or physical inventory hub.
     */
final class ToyStore extends Model
{
        use ToysDomainTrait;
        protected $table = 'toy_stores';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'location', 'metadata', 'correlation_id'];
        protected $casts = ['metadata' => 'json'];

        public function toys(): HasMany
        {
            return $this->hasMany(Toy::class, 'store_id');
        }
    }
