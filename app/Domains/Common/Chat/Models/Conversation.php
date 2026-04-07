<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Conversation extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'chat_conversations';

        protected $fillable = [
        'correlation_id',
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
