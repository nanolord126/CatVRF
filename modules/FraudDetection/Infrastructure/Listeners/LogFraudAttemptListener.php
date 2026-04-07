<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Infrastructure\Listeners;

use Psr\Log\LoggerInterface;
use Modules\FraudDetection\Domain\Events\FraudDetected;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogFraudAttemptListener implements ShouldQueue
{
    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public ?string $connection = 'database';

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public ?string $queue = 'listeners';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function handle(FraudDetected $event): void
    {
        $this->logger->channel('fraud_alerts')->critical('High-score fraudulent transaction detected!', [
            'transaction_id' => $event->transactionId,
            'user_id' => $event->userId,
            'score' => $event->score,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
