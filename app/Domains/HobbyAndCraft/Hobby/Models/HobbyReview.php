<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Models;

use HasFactory;
use HobbyDomainTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SoftDeletes, HobbyDomainTrait;

/**
     * HobbyReview Model
     */
final class HobbyReview extends Model
{
        use HobbyDomainTrait;

        protected $table = 'hobby_reviews';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id',
            'rating', 'comment', 'media', 'correlation_id'
        ];

        protected $casts = ['media' => 'json'];

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo
        {
            return $this->morphTo();
        }
}
