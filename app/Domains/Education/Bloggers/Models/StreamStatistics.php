<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamStatistics extends Model
{

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


        protected $hidden = ['correlation_id'];

        public function stream(): BelongsTo
        {
            return $this->belongsTo(Stream::class, 'stream_id');
        }
}
