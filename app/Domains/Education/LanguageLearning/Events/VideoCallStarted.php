<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Events;

use App\Domains\Education\LanguageLearning\Models\LanguageVideoCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Событие начала видеозвонка.
 * Канон 2026: Audit Log в слушателе или конструкторе.
 */
final class VideoCallStarted
{
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
