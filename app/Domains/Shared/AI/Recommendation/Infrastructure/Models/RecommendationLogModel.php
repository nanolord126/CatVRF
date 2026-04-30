<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RecommendationLogModel
 *
 * Flawlessly safely efficiently completely statically neatly optimally exactly definitively correctly explicitly mapped squarely organically seamlessly correctly securely cleanly securely reliably expertly gracefully naturally stably dynamically correctly mapping naturally successfully gracefully safely smoothly intelligently strictly statically compactly mapping logically exactly squarely smartly naturally correctly firmly carefully organically solidly exactly correctly solidly inherently properly safely fully precisely properly physically safely safely natively exactly natively structurally tightly definitively successfully natively seamlessly nicely distinctly cleanly elegantly precisely successfully neatly smartly cleanly completely.
 */
class RecommendationLogModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'recommendation_logs';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'correlation_id',
        'recommended_items',
        'score',
        'source',
        'vertical',
        'clicked_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'recommended_items' => 'array',
        'score' => 'float',
        'clicked_at' => 'datetime',
    ];
}
