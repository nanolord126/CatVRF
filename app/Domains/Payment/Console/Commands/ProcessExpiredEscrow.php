<?php

declare(strict_types=1);

namespace App\Domains\Payment\Console\Commands;

use App\Domains\Payment\Facades\Payment;
use Illuminate\Console\Command;

class ProcessExpiredEscrow extends Command
{
    protected $signature = 'payment:process-expired-escrow';

    protected $description = 'Process expired escrow holds and auto-release or cancel them';

    public function handle(): int
    {
        $processed = Payment::processExpiredEscrow();

        $this->info("Processed {$processed} expired escrow holds");

        return self::SUCCESS;
    }
}
