<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DataRetentionJob;
use Modules\Warehouse\Domain\Services\DataRetentionService;

/**
 * Run Data Retention Command
 * 
 * Manual command to execute data retention policies
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class RunDataRetentionCommand extends Command
{
    protected $signature = 'warehouse:data-retention:run {--dry-run : Show what would be deleted without actually deleting}';
    
    protected $description = 'Run data retention policies for warehouse data per 152-ФZ';

    public function __construct(
        private readonly DataRetentionService $dataRetentionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Running data retention policies...');

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no data will be deleted');
            
            // В dry-run режиме можно просто показать статистику
            $this->info('Logs retention period: 1 year');
            $this->info('Inventory counts retention period: 5 years');
            $this->info('Stock movements retention period: 7 years');
            $this->info('Batches retention: expiry + 1 year');
            
            return self::SUCCESS;
        }

        try {
            $results = $this->dataRetentionService->runAllRetentionPolicies();

            $this->info('Data retention completed:');
            $this->line("  - Logs anonymized: {$results['logs_anonymized']}");
            $this->line("  - Movements anonymized: {$results['movements_anonymized']}");
            $this->line("  - Inventory counts anonymized: {$results['inventory_counts_anonymized']}");
            $this->line("  - Expired batches deleted: {$results['expired_batches_deleted']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Data retention failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
