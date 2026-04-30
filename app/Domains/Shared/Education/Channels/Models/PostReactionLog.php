<?php

declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Str;

final class PostReactionLog extends Model
{
    public $timestamps = false;


    protected $table = 'post_reaction_logs';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'post_id',
        'tenant_id',
        'user_id',
        'session_hash',
        'ip_address',
        'emoji',
        'action',
        'fraud_score',
        'correlation_id',
        'reacted_at',
    ];

    protected $casts = [
        'fraud_score' => 'float',
        'reacted_at'  => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
