<?php

declare(strict_types=1);


namespace App\Domains\Content\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Лог реакций на посты.
 *
 * Используется для:
 *   - FraudMLService (детекция накрутки реакций)
 *   - Расширенной статистики (только extended тариф)
 *
 * Реакции анонимны для пользователей, но видны владельцу в статистике.
 */
final class PostReactionLog extends Model
{
    protected $table = 'post_reaction_logs';

    public $timestamps = false;

    protected $fillable = [
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
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
