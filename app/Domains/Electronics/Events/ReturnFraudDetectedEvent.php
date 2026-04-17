<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use App\Domains\Electronics\DTOs\ReturnFraudDetectionDto;
use App\Domains\Electronics\DTOs\FraudDetectionResultDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ReturnFraudDetectedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ReturnFraudDetectionDto $dto,
        public FraudDetectionResultDto $result,
        public string $correlationId,
    ) {
    }
}
