<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use App\Domains\Electronics\DTOs\SplitPaymentRequestDto;
use App\Domains\Electronics\DTOs\SplitPaymentResponseDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class SplitPaymentCompletedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public SplitPaymentRequestDto $request,
        public SplitPaymentResponseDto $response,
        public string $correlationId,
    ) {
    }
}
