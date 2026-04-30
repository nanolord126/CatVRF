<?php

declare(strict_types=1);

namespace App\Domains\Content\PodcastProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class PodcastProject extends Model
{
    protected $table = 'podcast_projects';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'producer_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'project_type',
        'production_hours',
        'due_date',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'production_hours' => 'integer',
        'due_date' => 'datetime',
        'tags' => 'json',
    ];

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
