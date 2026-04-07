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
     * AgeGroup Model (L1)
     * Critical for AI recommendation matching (min/max months).
     */
final class AgeGroup extends Model
{
        use ToysDomainTrait;
        protected $table = 'age_groups';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'min_age_months', 'max_age_months', 'correlation_id'];

        public function toys(): HasMany
        {
            return $this->hasMany(Toy::class, 'age_group_id');
        }
    }
