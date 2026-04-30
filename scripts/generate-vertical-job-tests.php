<?php

declare(strict_types=1);
/**
 * Batch Test Generation Script for Vertical Jobs
 *
 * Usage:
 * php scripts/generate-vertical-job-tests.php {VerticalName}
 * php scripts/generate-vertical-job-tests.php all
 *
 * Examples:
 * php scripts/generate-vertical-job-tests.php Auto
 * php scripts/generate-vertical-job-tests.php all
 */
$vertical = $argv[1] ?? null;

if (! $vertical) {
    echo "Usage: php scripts/generate-vertical-job-tests.php {VerticalName|all}\n";
    echo "Example: php scripts/generate-vertical-job-tests.php Auto\n";
    exit(1);
}

$verticals = [
    'Auto', 'RealEstate', 'Fashion', 'Travel', 'Hotels', 'Electronics', 'Fitness', 'Sports',
    'Luxury', 'Insurance', 'Legal', 'Logistics', 'Education', 'CRM', 'Delivery', 'Payment',
    'Analytics', 'Consulting', 'Content', 'Freelance', 'EventPlanning', 'Staff', 'Inventory',
    'Taxi', 'Tickets', 'Wallet', 'Pet', 'WeddingPlanning', 'Veterinary', 'ToysAndGames',
    'Advertising', 'CarRental', 'Finances', 'Flowers', 'Furniture', 'Pharmacy', 'Photography',
    'ShortTermRentals', 'SportsNutrition', 'PersonalDevelopment', 'HomeServices', 'Gardening',
    'Geo', 'GeoLogistics', 'GroceryAndDelivery', 'FarmDirect', 'MeatShops', 'OfficeCatering',
    'PartySupplies', 'Confectionery', 'ConstructionAndRepair', 'CleaningServices', 'Communication',
    'BooksAndLiterature', 'Collectibles', 'HobbyAndCraft', 'HouseholdGoods', 'Marketplace',
    'MusicAndInstruments', 'VeganProducts', 'Art',
];

$verticalsToProcess = $vertical === 'all' ? $verticals : [$vertical];

foreach ($verticalsToProcess as $vert) {
    generateVerticalJobTests($vert);
}

function generateVerticalJobTests(string $vertical): void
{
    $jobsDir = __DIR__."/../app/Domains/{$vertical}/Jobs";
    $testsDir = __DIR__."/../tests/Unit/Domains/{$vertical}";

    if (! is_dir($jobsDir)) {
        echo "Skipping {$vertical}: Jobs directory not found\n";

        return;
    }

    if (! is_dir($testsDir)) {
        mkdir($testsDir, 0755, true);
    }

    $files = glob("{$jobsDir}/*.php");

    if (empty($files)) {
        echo "Skipping {$vertical}: No job files found\n";

        return;
    }

    echo "Processing {$vertical}...\n";

    foreach ($files as $file) {
        $className = pathinfo($file, PATHINFO_FILENAME);
        $testClassName = $className.'Test';
        $testFilePath = "{$testsDir}/{$testClassName}.php";

        if (file_exists($testFilePath)) {
            echo "  Skipped {$testClassName}: Test already exists\n";

            continue;
        }

        $testContent = generateTestClass($vertical, $className, $testClassName);
        file_put_contents($testFilePath, $testContent);

        echo "  Created {$testClassName}\n";
    }

    echo "Completed {$vertical}\n\n";
}

function generateTestClass(string $vertical, string $className, string $testClassName): string
{
    $lowerVertical = strtolower($vertical);
    $namespace = "App\\Domains\\{$vertical}\\Jobs";

    return <<<PHP
<?php declare(strict_types=1);

namespace Tests\\Unit\\Domains\\{$vertical};

use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\\Support\\Str;
use Tests\\TestCase;
use {$namespace}\\{$className};

final class {$testClassName} extends TestCase
{
    use RefreshDatabase;

    public function test_{$lowerVertical}_job_implements_should_queue(): void
    {
        \$job = new {$className}();
        
        \$this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, \$job);
    }

    public function test_{$lowerVertical}_job_has_queue_traits(): void
    {
        \$job = new {$className}();
        
        \$this->assertTrue(method_exists(\$job, 'onQueue'));
        \$this->assertTrue(method_exists(\$job, 'tags'));
        \$this->assertTrue(method_exists(\$job, 'failed'));
    }

    public function test_{$lowerVertical}_job_has_retry_configuration(): void
    {
        \$job = new {$className}();
        
        \$this->assertObjectHasProperty('tries', \$job);
        \$this->assertObjectHasProperty('backoff', \$job);
        \$this->assertObjectHasProperty('timeout', \$job);
    }

    public function test_{$lowerVertical}_job_tags_are_correct(): void
    {
        \$job = new {$className}();
        
        if (method_exists(\$job, 'tags')) {
            \$tags = \$job->tags();
            
            \$this->assertIsArray(\$tags);
            \$this->assertContains('{$lowerVertical}', \$tags);
        }
    }

    public function test_{$lowerVertical}_job_failed_handler(): void
    {
        \$job = new {$className}();
        
        if (method_exists(\$job, 'failed')) {
            Log::spy();
            
            \$exception = new \\RuntimeException('Test exception');
            \$job->failed(\$exception);
            
            Log::shouldHaveReceived('channel')->with('audit');
        }
    }
}
PHP;
}
