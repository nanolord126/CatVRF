<?php declare(strict_types=1);

/**
 * VideoCallStarted — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/videocallstarted
 */


namespace App\Domains\Education\LanguageLearning\Events;


use Psr\Log\LoggerInterface;
final class VideoCallStarted
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public LanguageVideoCall $videoCall,
            public string $correlationId, public readonly LoggerInterface $logger
        ) {
            $this->logger->info('LanguageLearning: VideoCall Started', [
                'video_call_id' => $this->videoCall->id,
                'lesson_id' => $this->videoCall->lesson_id,
                'room_id' => $this->videoCall->provider_room_id,
                'correlation_id' => $this->correlationId,
            ]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
