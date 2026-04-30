<?php

declare(strict_types=1);

namespace App\Domains\Payment\Console\Commands;

use App\Domains\Payment\Facades\Payment;
use Illuminate\Console\Command;

class ProcessOutboxMessages extends Command
{
    protected $signature = 'payment:process-outbox';

    protected $description = 'Process pending outbox messages for webhook delivery';

    public function handle(): int
    {
        $processed = Payment::processOutbox();

        $this->info("Processed {$processed} outbox messages");

        return self::SUCCESS;
    }
}
