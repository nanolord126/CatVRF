<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Generate Event System tests for all verticals
 */
final class GenerateVerticalEventTestsCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }
    protected $signature = 'events:generate-tests {--vertical= : Specific vertical}';

    protected $description = 'Generate Event Store tests for verticals';

    private array $verticals = [
        'Beauty', 'Food', 'RealEstate', 'Fashion', 'Travel', 'Auto', 'Hotels', 'Medical',
        'Electronics', 'Fitness', 'Sports', 'Luxury', 'Insurance', 'Legal', 'Logistics',
        'Education', 'CRM', 'Delivery', 'Payment', 'Analytics', 'Consulting', 'Content',
        'Freelance', 'EventPlanning', 'Staff', 'Inventory', 'Taxi', 'Tickets', 'Wallet',
        'Pet', 'WeddingPlanning', 'Veterinary', 'ToysAndGames', 'Advertising', 'CarRental',
        'Finances', 'Flowers', 'Furniture', 'Pharmacy', 'Photography', 'ShortTermRentals',
        'SportsNutrition', 'PersonalDevelopment', 'HomeServices', 'Gardening', 'Geo',
        'GeoLogistics', 'GroceryAndDelivery', 'FarmDirect', 'MeatShops', 'OfficeCatering',
        'PartySupplies', 'Confectionery', 'ConstructionAndRepair', 'CleaningServices',
        'Communication', 'BooksAndLiterature', 'Collectibles', 'HobbyAndCraft',
        'HouseholdGoods', 'Marketplace', 'MusicAndInstruments', 'VeganProducts', 'Art',
    ];

    private int $testsCreated = 0;

    public function handle(): int
    {
        $vertical = $this->option('vertical');

        if ($vertical) {
            $this->generateTest($vertical);
        } else {
            $this->info('Generating Event Store tests for all verticals...');
            $progressBar = $this->output->createProgressBar(count($this->verticals));
            $progressBar->start();

            foreach ($this->verticals as $vertical) {
                $this->generateTest($vertical);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->info("Created {$this->testsCreated} tests");

        return self::SUCCESS;
    }

    private function generateTest(string $vertical): void
    {
        $testPath = $this->laravel->basePath("tests/Unit/Domains/{$vertical}/EventStoreTest.php");

        if ($this->files->exists($testPath)) {
            return;
        }

        $directory = dirname($testPath);
        if (! $this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $template = $this->getTestTemplate($vertical);
        $this->files->put($testPath, $template);
        $this->testsCreated++;
    }

    private function getTestTemplate(string $vertical): string
    {
        $lowerVertical = strtolower($vertical);
        $entityName = match($vertical) {
            'Beauty' => 'appointment',
            'Food' => 'order',
            'Fashion' => 'order',
            'Travel' => 'booking',
            'Auto' => 'service_order',
            'Hotels' => 'booking',
            'Medical' => 'appointment',
            'Electronics' => 'warranty_claim',
            'Fitness' => 'membership',
            'Sports' => 'booking',
            'Luxury' => 'order',
            'Insurance' => 'policy',
            'Legal' => 'consultation',
            'Logistics' => 'shipment',
            'Education' => 'enrollment',
            default => 'entity',
        };

        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\\{$vertical};

use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Infrastructure\Persistence\EventStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventStoreTest extends TestCase
{
    use RefreshDatabase;

    private EventStore \$eventStore;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->eventStore = \$this->app->make(EventStore::class);
    }

    public function test_publishes_domain_event_to_outbox(): void
    {
        \$event = new Test{$vertical}DomainEvent('{$entityName}-123', 'user-456');

        \$this->eventStore->publish([\$event]);

        \$this->assertDatabaseHas('outbox_messages', [
            'event_type' => '{$lowerVertical}.test.event',
            'status' => 'pending',
        ]);
    }

    public function test_publishes_multiple_domain_events(): void
    {
        \$events = [
            new Test{$vertical}DomainEvent('{$entityName}-1', 'user-1'),
            new Test{$vertical}DomainEvent('{$entityName}-2', 'user-2'),
        ];

        \$this->eventStore->publish(\$events);

        \$this->assertDatabaseCount('outbox_messages', 2);
    }
}

final class Test{$vertical}DomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string \${$entityName}Id,
        private readonly string \$userId,
        mixed \$correlationId = null,
    ) {
        parent::__construct(\$correlationId);
    }

    public function eventName(): string
    {
        return '{$lowerVertical}.test.event';
    }

    public function toArray(): array
    {
        return [
            '{$entityName}_id' => \$this->{$entityName}Id,
            'user_id' => \$this->userId,
        ];
    }
}
PHP;
    }
}
