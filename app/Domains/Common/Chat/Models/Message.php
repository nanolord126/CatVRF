<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Message extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'chat_messages';

        protected $fillable = [
            'uuid',
            'conversation_id',
            'sender_id',
            'content',
            'type',
            'payload',
            'correlation_id',
        ];

        protected $casts = [
            'payload' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        }

        public function conversation(): BelongsTo
        {
            return $this->belongsTo(Conversation::class, 'conversation_id');
        }

        public function sender(): BelongsTo
        {
            return $this->belongsTo(User::class, 'sender_id');
        }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}