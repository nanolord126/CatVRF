<?php declare(strict_types=1);

namespace App\Domains\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Пост бизнес-канала.
 *
 * Статусы: draft | pending_moderation | published | archived | rejected
 * Видимость: b2c | b2b | all
 *
 * Реакции хранятся в JSON поле reactions: {"like":12,"heart":5,"fire":3}
 * Разрешённые emoji строго по нормам РФ — см. config('channels.allowed_reactions')
 */
final class Post extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'posts';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'channel_id',
        'tenant_id',
        'title',
        'content',
        'slug',
        'status',
        'visibility',
        'published_at',
        'scheduled_at',
        'reactions',
        'poll',
        'views_count',
        'reactions_count',
        'is_promo',
        'is_moderated',
        'moderated_by',
        'moderated_at',
        'moderation_comment',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'published_at'    => 'datetime',
        'scheduled_at'    => 'datetime',
        'moderated_at'    => 'datetime',
        'reactions'       => 'json',
        'poll'            => 'json',
        'tags'            => 'json',
        'views_count'     => 'integer',
        'reactions_count' => 'integer',
        'is_promo'        => 'boolean',
        'is_moderated'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            'tenant',
            fn ($query) => $query->where('posts.tenant_id', tenant('id') ?? '0')
        );
    }

    // ──────────────────────────────────────────────────────
    // Связи
    // ──────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(BusinessChannel::class, 'channel_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class, 'post_id')->orderBy('sort_order');
    }

    public function reactionLogs(): HasMany
    {
        return $this->hasMany(PostReactionLog::class, 'post_id');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PostStatDaily::class, 'post_id');
    }

    // ──────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null
            && $this->status === 'draft';
    }

    /** Получить реакцию по emoji (0 если нет) */
    public function getReactionCount(string $emoji): int
    {
        return (int) (($this->reactions ?? [])[$emoji] ?? 0);
    }

    /** Применить реакцию: +1 для add, -1 для remove */
    public function applyReaction(string $emoji, string $action): void
    {
        $reactions = $this->reactions ?? [];
        $current   = (int) ($reactions[$emoji] ?? 0);

        if ($action === 'add') {
            $reactions[$emoji] = $current + 1;
        } elseif ($action === 'remove' && $current > 0) {
            $reactions[$emoji] = $current - 1;
            if ($reactions[$emoji] === 0) {
                unset($reactions[$emoji]);
            }
        }

        $this->reactions       = $reactions;
        $this->reactions_count = (int) array_sum($reactions);
    }

    /** Видимость удовлетворяет требуемой */
    public function isVisibleFor(string $audience): bool
    {
        if ($this->visibility === 'all') {
            return true;
        }

        return $this->visibility === $audience;
    }
}
