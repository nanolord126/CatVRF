<?php

declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class PostStatDaily extends Model
{
    protected $table = 'post_stats_daily';

    protected $fillable = [
        'uuid',
        'correlation_id',
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

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
