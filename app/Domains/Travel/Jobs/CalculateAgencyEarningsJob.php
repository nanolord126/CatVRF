<?php

declare(strict_types=1);

namespace App\Domains\Travel\Jobs;


use Psr\Log\LoggerInterface;
use App\Domains\Travel\Models\TravelAgency;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FraudControlService;

final class CalculateAgencyEarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $agencyId,
        private readonly string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {

    }

    /**
     * Execute the job.
     */
    public function handle(WalletService $walletService): void
    {
        $correlationId = $this->correlationId;
        $this->logger->info(
            '[CalculateAgencyEarningsJob] Started.',
            ['correlation_id' => $correlationId]
        );

        try {
            $this->db->transaction(function () use ($walletService, $correlationId) {
                $agency = TravelAgency::findOrFail($this->agencyId);

                // Dummy logic for earning calculation
                $earnings = $agency->bookings()->where('status', 'completed')->sum('price') * 0.1;

                if ($earnings > 0) {
                    $walletService->credit(
                        $agency->wallet->id,
                        (int) ($earnings * 100),
                        \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,
                    $correlationId, null, null, [
                        'agency_id' => $this->agencyId,
                        \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                    'agency_id' => $this->agencyId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

            $this->fail($e);
        }
    }
}
