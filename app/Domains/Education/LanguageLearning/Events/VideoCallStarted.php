<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VideoCallStarted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public LanguageVideoCall $videoCall,
            public string $correlationId
        ) {
            Log::channel('audit')->info('LanguageLearning: VideoCall Started', [
                'video_call_id' => $this->videoCall->id,
                'lesson_id' => $this->videoCall->lesson_id,
                'room_id' => $this->videoCall->provider_room_id,
                'correlation_id' => $this->correlationId,
            ]);
        }
}
