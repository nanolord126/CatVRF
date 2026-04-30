<?php

declare(strict_types=1);

namespace App\Domains\Payment\Console\Commands;

use App\Domains\Payment\Facades\Payment;
use Illuminate\Console\Command;

class ProcessDueSubscriptions extends Command
{
    protected $signature = 'payment:process-due-subscriptions';

    protected $description = 'Process recurring subscriptions that are due for billing';

    public function handle(): int
    {
        $processed = Payment::processDueSubscriptions();

        $this->info("Processed {$processed} due subscriptions");

        return self::SUCCESS;
    }
}
