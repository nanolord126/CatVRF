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
     * VeganReview Model - Verification of quality and taste.
     */
final class VeganReview extends Model
{
        protected $table = 'vegan_reviews';
        protected $fillable = ['uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id', 'rating', 'comment', 'meta', 'correlation_id'];
        protected $casts = ['meta' => 'json', 'rating' => 'integer'];

        public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }
    }
