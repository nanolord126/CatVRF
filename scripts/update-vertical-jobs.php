<?php

declare(strict_types=1);
/**
 * Batch Update Script for Vertical Job Queue Integration
 *
 * Usage:
 * php scripts/update-vertical-jobs.php {VerticalName}
 * php scripts/update-vertical-jobs.php all
 *
 * Examples:
 * php scripts/update-vertical-jobs.php Medical
 * php scripts/update-vertical-jobs.php Food
 * php scripts/update-vertical-jobs.php all
 */
$vertical = $argv[1] ?? null;

if (! $vertical) {
    echo "Usage: php scripts/update-vertical-jobs.php {VerticalName|all}\n";
    echo "Example: php scripts/update-vertical-jobs.php Medical\n";
    exit(1);
}

$verticals = [
    'Medical', 'Food', 'Beauty', 'Auto', 'RealEstate', 'Fashion', 'Travel',
    'Hotels', 'Electronics', 'Fitness', 'Sports', 'Luxury', 'Insurance', 'Legal',
    'Logistics', 'Education', 'CRM', 'Delivery', 'Payment', 'Analytics', 'Consulting',
    'Content', 'Freelance', 'EventPlanning', 'Staff', 'Inventory', 'Taxi', 'Tickets',
    'Wallet', 'Pet', 'WeddingPlanning', 'Veterinary', 'ToysAndGames', 'Advertising',
    'CarRental', 'Finances', 'Flowers', 'Furniture', 'Pharmacy', 'Photography',
    'ShortTermRentals', 'SportsNutrition', 'PersonalDevelopment', 'HomeServices',
    'Gardening', 'Geo', 'GeoLogistics', 'GroceryAndDelivery', 'FarmDirect',
    'MeatShops', 'OfficeCatering', 'PartySupplies', 'Confectionery',
    'ConstructionAndRepair', 'CleaningServices', 'Communication', 'BooksAndLiterature',
    'Collectibles', 'HobbyAndCraft', 'HouseholdGoods', 'Marketplace',
    'MusicAndInstruments', 'VeganProducts', 'Art',
];

$verticalsToProcess = $vertical === 'all' ? $verticals : [$vertical];

foreach ($verticalsToProcess as $vert) {
    updateVerticalJobs($vert);
}

function updateVerticalJobs(string $vertical): void
{
    $jobsDir = __DIR__."/../app/Domains/{$vertical}/Jobs";

    if (! is_dir($jobsDir)) {
        echo "Skipping {$vertical}: Jobs directory not found\n";

        return;
    }

    $files = glob("{$jobsDir}/*.php");

    if (empty($files)) {
        echo "Skipping {$vertical}: No job files found\n";

        return;
    }

    echo "Processing {$vertical}...\n";

    foreach ($files as $file) {
        $content = file_get_contents($file);
        $originalContent = $content;
        $changes = [];

        // 1. Add ShouldQueue interface if missing
        if (! str_contains($content, 'implements ShouldQueue')) {
            $content = str_replace(
                'final class',
                'implements ShouldQueue'.PHP_EOL.'final class',
                $content
            );

            if (! str_contains($content, 'use Illuminate\Contracts\Queue\ShouldQueue;')) {
                $content = str_replace(
                    'use Psr\Log\LoggerInterface;',
                    'use Illuminate\Contracts\Queue\ShouldQueue;'.PHP_EOL.'use Psr\Log\LoggerInterface;',
                    $content
                );
            }
            $changes[] = 'Added ShouldQueue interface';
        }

        // 2. Add traits if missing
        if (! str_contains($content, 'use Dispatchable')) {
            $content = str_replace(
                'use Illuminate\Queue\SerializesModels;',
                'use Illuminate\Queue\SerializesModels;'.PHP_EOL.PHP_EOL.'    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;',
                $content
            );
            $changes[] = 'Added queue traits';
        }

        // 3. Add queue assignment in constructor if missing
        if (! str_contains($content, '$this->onQueue')) {
            $content = preg_replace(
                '/\}\s*$/',
                '        $this->onQueue(\'default\');'.PHP_EOL.'    }',
                $content,
                1
            );
            $changes[] = 'Added queue assignment';
        }

        // 4. Add tags() method if missing
        if (! str_contains($content, 'public function tags()')) {
            $content = preg_replace(
                '/(public function handle\(\))/',
                'public function tags(): array'.PHP_EOL.'    {'.PHP_EOL.'        return [\''.strtolower($vertical).'\', \'job\'];'.PHP_EOL.'    }'.PHP_EOL.PHP_EOL.'    $1',
                $content
            );
            $changes[] = 'Added tags() method';
        }

        // 5. Add failed() method if missing
        if (! str_contains($content, 'public function failed')) {
            $content = preg_replace(
                '/(\}\s*$)/',
                PHP_EOL.PHP_EOL.'    public function failed(\Throwable $exception): void'.PHP_EOL.'    {'.PHP_EOL.'        \Log::error(\''.strtolower($vertical).' job failed\', ['.PHP_EOL.'            \'error\' => $exception->getMessage(),'.PHP_EOL.'        ]);'.PHP_EOL.'    }'.PHP_EOL.'$1',
                $content
            );
            $changes[] = 'Added failed() method';
        }

        // 6. Add backoff property if missing
        if (! str_contains($content, 'public array $backoff')) {
            $content = str_replace(
                'public int $tries',
                'public array $backoff = [60, 300, 900];'.PHP_EOL.'    public int $tries',
                $content
            );
            $changes[] = 'Added backoff property';
        }

        // 7. Add timeout property if missing
        if (! str_contains($content, 'public int $timeout')) {
            $content = str_replace(
                'public int $tries',
                'public int $timeout = 120;'.PHP_EOL.'    public int $tries',
                $content
            );
            $changes[] = 'Added timeout property';
        }

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo '  Updated '.basename($file).': '.implode(', ', $changes)."\n";
        } else {
            echo '  Skipped '.basename($file).": Already compliant\n";
        }
    }

    echo "Completed {$vertical}\n\n";
}

echo "Batch update complete!\n";
