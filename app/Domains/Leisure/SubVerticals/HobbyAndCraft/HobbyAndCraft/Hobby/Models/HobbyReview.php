<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Hobby\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * HobbyReview Model
 */
final class HobbyReview extends Model
{
    use HobbyDomainTrait;
    use TenantScoped;

    protected $table = 'hobby_reviews';

    protected $fillable = [
        'uuid', 'tenant_id', 'user_id', 'reviewable_type', 'reviewable_id',
        'rating', 'comment', 'media', 'correlation_id',
    ];

    protected $casts = ['media' => 'json'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
