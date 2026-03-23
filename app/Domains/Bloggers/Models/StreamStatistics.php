<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stream Statistics
 */
class StreamStatistics extends BaseModel
{
    use HasFactory;

    protected $table = 'stream_statistics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'stream_id',
        'unique_viewers',
        'total_messages',
        'total_gifts',
        'total_gifts_revenue',
        'total_products_sold',
        'total_commerce_revenue',
        'average_session_duration',
        'engagement_rate',
        'viewer_countries',
        'traffic_sources',
        'correlation_id',
    ];

    protected $casts = [
        'viewer_countries' => 'json',
        'traffic_sources' => 'json',
        'average_session_duration' => 'float',
        'engagement_rate' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }
}
