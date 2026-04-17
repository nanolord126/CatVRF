<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\SoftDeletes;
final class Conversation extends Model
{
    use HasFactory, TenantScoped;


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
