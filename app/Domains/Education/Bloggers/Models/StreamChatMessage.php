<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamChatMessage extends Model
{
    use HasFactory;

        protected $table = 'stream_chat_messages';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'stream_id',
            'user_id',
            'message',
            'message_type',
            'is_pinned',
            'is_deleted',
            'moderation_status',
            'moderation_note',
            'tags',
            'correlation_id',
            'pinned_at',
        ];

        protected $casts = [
            'tags' => 'json',
            'pinned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_deleted' => 'boolean',
        ];

        protected $hidden = ['correlation_id'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('stream_chat_messages.tenant_id', tenant()->id);
            });
        }

        public function stream(): BelongsTo
        {
            return $this->belongsTo(Stream::class, 'stream_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function isApproved(): bool
        {
            return $this->moderation_status === 'approved';
        }

        public function isRejected(): bool
        {
            return $this->moderation_status === 'rejected';
        }

        public function isPending(): bool
        {
            return $this->moderation_status === 'pending';
        }
}
