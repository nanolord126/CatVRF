<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        public function post(): BelongsTo
        {
            return $this->belongsTo(Post::class, 'post_id');
        }
}
