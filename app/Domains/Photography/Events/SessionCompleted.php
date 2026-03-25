declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Events;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SessionCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SessionCompleted
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public function __construct(
		public readonly PhotoSession $session,
		public readonly string $correlationId
	) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
