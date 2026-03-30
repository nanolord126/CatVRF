<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Conversation extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'chat_conversations';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'type',
            'metadata',
        ];

        protected $casts = [
            'metadata' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        }

        public function participants(): BelongsToMany
        {
            return $this->belongsToMany(User::class, 'chat_participants', 'conversation_id', 'user_id')
                ->withPivot('last_read_at')
                ->withTimestamps();
        }

        public function messages(): HasMany
        {
            return $this->hasMany(Message::class, 'conversation_id');
        }
}
