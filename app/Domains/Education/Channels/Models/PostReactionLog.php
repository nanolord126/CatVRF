<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PostReactionLog extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
