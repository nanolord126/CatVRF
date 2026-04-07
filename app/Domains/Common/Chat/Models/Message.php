<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
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

        

}