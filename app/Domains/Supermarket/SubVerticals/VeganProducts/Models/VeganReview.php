<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * VeganReview Model - Verification of quality and taste.
 */
final class VeganReview extends Model
{
    protected $table = 'vegan_reviews';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id', 'rating', 'comment', 'meta', 'correlation_id'];

    protected $casts = ['meta' => 'json', 'rating' => 'integer'];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
