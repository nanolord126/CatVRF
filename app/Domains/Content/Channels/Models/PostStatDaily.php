<?php

declare(strict_types=1);


namespace App\Domains\Content\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ежедневная статистика поста.
 *
 * Базовая статистика (просмотры, реакции) — любой тариф.
 * Расширенная (CTR, гео, устройства) — только extended тариф.
 */
final class PostStatDaily extends Model
{
    protected $table = 'post_stats_daily';

    protected $fillable = [
        'post_id',
        'tenant_id',
        'stat_date',
        'views',
        'unique_views',
        'reactions_total',
        'link_clicks',
        'reactions_breakdown',
        'geo_breakdown',
        'device_breakdown',
    ];

    protected $casts = [
        'stat_date'           => 'date',
        'views'               => 'integer',
        'unique_views'        => 'integer',
        'reactions_total'     => 'integer',
        'link_clicks'         => 'integer',
        'reactions_breakdown' => 'json',
        'geo_breakdown'       => 'json',
        'device_breakdown'    => 'json',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
