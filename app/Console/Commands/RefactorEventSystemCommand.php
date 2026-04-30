<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Command to refactor Event System for all verticals
 * Converts Laravel Events to Domain Events, refactors Listeners, creates Application Services
 */
final class RefactorEventSystemCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly LogManager $log,
        private readonly CacheManager $cache,
    ) {
        parent::__construct();
    }
    protected $signature = 'events:refactor
                            {--vertical= : Specific vertical to refactor (default: all)}
                            {--dry-run : Show what would be changed without applying}
                            {--force : Apply changes without confirmation}';

    protected $description = 'Refactor Event System for verticals: convert to Domain Events, refactor Listeners, create Application Services';

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

    private int $eventsConverted = 0;

    private int $listenersRefactored = 0;

    private int $servicesCreated = 0;

    private int $testsCreated = 0;

    public function handle(): int
    {
        $vertical = $this->option('vertical');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($vertical) {
            $this->info("Refactoring Event System for vertical: {$vertical}");
            $this->processVertical($vertical, $dryRun, $force);
        } else {
            $this->info('Refactoring Event System for all verticals...');

            if (! $dryRun && ! $force) {
                if (! $this->confirm('This will refactor all 64 verticals. Continue?')) {
                    return self::SUCCESS;
                }
            }

            $progressBar = $this->output->createProgressBar(count($this->verticals));
            $progressBar->start();

            foreach ($this->verticals as $vertical) {
                $this->processVertical($vertical, $dryRun, $force);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Refactoring Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Events Converted', $this->eventsConverted],
                ['Listeners Refactored', $this->listenersRefactored],
                ['Application Services Created', $this->servicesCreated],
                ['Tests Created', $this->testsCreated],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were applied');
        }

        return self::SUCCESS;
    }

    private function processVertical(string $vertical, bool $dryRun, bool $force): void
    {
        $verticalPath = app_path("Domains/{$vertical}");

        if (! $this->files->exists($verticalPath)) {
            $this->warn("Vertical {$vertical} not found, skipping...");

            return;
        }

        // Process Events
        $this->processEvents($vertical, $verticalPath, $dryRun, $force);

        // Process Listeners
        $this->processListeners($vertical, $verticalPath, $dryRun, $force);

        // Create Application Services
        $this->createApplicationServices($vertical, $verticalPath, $dryRun, $force);

        // Create Tests
        $this->createTests($vertical, $verticalPath, $dryRun, $force);
    }

    private function processEvents(string $vertical, string $verticalPath, bool $dryRun, bool $force): void
    {
        $eventsPath = "{$verticalPath}/Events";
        if (! $this->files->exists($eventsPath)) {
            return;
        }

        $events = $this->files->files($eventsPath);

        foreach ($events as $eventFile) {
            if ($eventFile->getExtension() !== 'php') {
                continue;
            }

            $eventClass = $this->getFullClassName($eventFile->getPathname());

            if (! $this->isLaravelEvent($eventClass)) {
                continue; // Already a Domain Event or Application Event
            }

            $eventName = $eventFile->getFilenameWithoutExtension();
            $domainEventName = $eventName.'DomainEvent';

            if ($dryRun) {
                $this->line("  [Event] Would convert {$eventName} to {$domainEventName}");
                $this->eventsConverted++;

                continue;
            }

            $this->convertToDomainEvent($vertical, $eventClass, $domainEventName);
            $this->eventsConverted++;
        }
    }

    private function processListeners(string $vertical, string $verticalPath, bool $dryRun, bool $force): void
    {
        $listenersPath = "{$verticalPath}/Listeners";
        if (! $this->files->exists($listenersPath)) {
            return;
        }

        $listeners = $this->files->files($listenersPath);

        foreach ($listeners as $listenerFile) {
            if ($listenerFile->getExtension() !== 'php') {
                continue;
            }

            $listenerClass = $this->getFullClassName($listenerFile->getPathname());

            if ($this->isThinListener($listenerClass)) {
                continue; // Already refactored
            }

            $listenerName = $listenerFile->getFilenameWithoutExtension();

            if ($dryRun) {
                $this->line("  [Listener] Would refactor {$listenerName}");
                $this->listenersRefactored++;

                continue;
            }

            $this->refactorListener($vertical, $listenerClass, $listenerName);
            $this->listenersRefactored++;
        }
    }

    private function createApplicationServices(string $vertical, string $verticalPath, bool $dryRun, bool $force): void
    {
        $servicesPath = "{$verticalPath}/Application/Services";

        if (! $this->files->exists($servicesPath)) {
            $this->files->makeDirectory($servicesPath, 0755, true);
        }

        // Create Notification Service if not exists
        $notificationServicePath = "{$servicesPath}/{$vertical}NotificationService.php";

        if (! $this->files->exists($notificationServicePath)) {
            if ($dryRun) {
                $this->line("  [Service] Would create {$vertical}NotificationService");
                $this->servicesCreated++;
            } else {
                $this->createNotificationService($vertical, $notificationServicePath);
                $this->servicesCreated++;
            }
        }

        // Create Cache Invalidation Service if not exists
        $cacheServicePath = "{$servicesPath}/CacheInvalidationService.php";

        if (! $this->files->exists($cacheServicePath)) {
            if ($dryRun) {
                $this->line('  [Service] Would create CacheInvalidationService');
                $this->servicesCreated++;
            } else {
                $this->createCacheInvalidationService($vertical, $cacheServicePath);
                $this->servicesCreated++;
            }
        }
    }

    private function createTests(string $vertical, string $verticalPath, bool $dryRun, bool $force): void
    {
        $testPath = $this->laravel->basePath("tests/Unit/Domains/{$vertical}");

        if (! $this->files->exists($testPath)) {
            $this->files->makeDirectory($testPath, 0755, true);
        }

        // Create Event Store Test
        $eventStoreTestPath = "{$testPath}/EventStoreTest.php";

        if (! $this->files->exists($eventStoreTestPath)) {
            if ($dryRun) {
                $this->line('  [Test] Would create EventStoreTest');
                $this->testsCreated++;
            } else {
                $this->createEventStoreTest($vertical, $eventStoreTestPath);
                $this->testsCreated++;
            }
        }

        // Create Application Service Test
        $serviceTestPath = "{$testPath}/ApplicationServiceTest.php";

        if (! $this->files->exists($serviceTestPath)) {
            if ($dryRun) {
                $this->line('  [Test] Would create ApplicationServiceTest');
                $this->testsCreated++;
            } else {
                $this->createApplicationServiceTest($vertical, $serviceTestPath);
                $this->testsCreated++;
            }
        }
    }

    private function isLaravelEvent(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        // Check if it extends a base Laravel Event class
        return ! $reflection->isSubclassOf('App\Shared\Domain\Events\DomainEvent')
            && ! $reflection->isSubclassOf('App\Shared\Application\Events\ApplicationEvent');
    }

    private function isThinListener(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);
        $handleMethod = $reflection->getMethod('handle');

        // Check if handle method is short (thin listener)
        $source = file($reflection->getFileName());
        $startLine = $handleMethod->getStartLine() - 1;
        $endLine = $handleMethod->getEndLine();
        $methodLines = array_slice($source, $startLine, $endLine - $startLine);

        // Thin listener: less than 20 lines in handle method
        return count($methodLines) < 20;
    }

    private function getFullClassName(string $filePath): string
    {
        $content = $this->files->get($filePath);

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
            $className = basename($filePath, '.php');

            return "{$namespace}\\{$className}";
        }

        return basename($filePath, '.php');
    }

    private function convertToDomainEvent(string $vertical, string $eventClass, string $domainEventName): void
    {
        // Implementation would parse the Laravel Event and convert to Domain Event
        // This is a placeholder - actual implementation would use AST parsing
        $this->info("Converting {$eventClass} to Domain Event...");
    }

    private function refactorListener(string $vertical, string $listenerClass, string $listenerName): void
    {
        // Implementation would refactor the listener to thin wrapper
        // This is a placeholder - actual implementation would use AST parsing
        $this->info("Refactoring {$listenerClass}...");
    }

    private function createNotificationService(string $vertical, string $path): void
    {
        $template = $this->getNotificationServiceTemplate($vertical);
        $this->files->put($path, $template);
    }

    private function createCacheInvalidationService(string $vertical, string $path): void
    {
        $template = $this->getCacheInvalidationServiceTemplate($vertical);
        $this->files->put($path, $template);
    }

    private function createEventStoreTest(string $vertical, string $path): void
    {
        $template = $this->getEventStoreTestTemplate($vertical);
        $this->files->put($path, $template);
    }

    private function createApplicationServiceTest(string $vertical, string $path): void
    {
        $template = $this->getApplicationServiceTestTemplate($vertical);
        $this->files->put($path, $template);
    }

    private function getNotificationServiceTemplate(string $vertical): string
    {
        $camelVertical = Str::camel($vertical);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Domains\\{$vertical}\Application\Services;

use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Mail\Mailer;

/**
 * Application Service: Handles {$vertical} notifications
 * Contains business logic for notification processing
 */
final readonly class {$vertical}NotificationService
{
    public function __construct()
    {
        // Inject notification dependencies here
    }

    public function sendConfirmation(string \$entityId, string \$userId): void
    {
        try {
            // Send email notification
            // Mail::to(\$user->email)->send(new ConfirmationMail(\$entityId));

            // Send SMS notification
            // SMS::send(\$user->phone, "Your {$camelVertical} has been confirmed");

            // Send push notification
            // PushNotification::send(\$userId, [
            //     'title' => '{$vertical} Confirmed',
            //     'body' => "Your {$camelVertical} has been confirmed",
            // ]);

            \$this->logger->info('{$vertical} confirmation sent', [
                'entity_id' => \$entityId,
                'user_id' => \$userId,
            ]);

        } catch (\Throwable \$e) {
            \$this->logger->error('Failed to send {$vertical} confirmation', [
                'entity_id' => \$entityId,
                'error' => \$e->getMessage(),
            ]);
            throw \$e;
        }
    }

    public function sendReminder(string \$entityId, string \$userId, \DateTimeImmutable \$dueAt): void
    {
        // Implementation for reminder notifications
    }

    public function sendCancellation(string \$entityId, string \$userId, string \$reason): void
    {
        // Implementation for cancellation notifications
    }
}
PHP;
    }

    private function getCacheInvalidationServiceTemplate(string $vertical): string
    {
        $camelVertical = Str::camel($vertical);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Domains\\{$vertical}\Application\Services;


/**
 * Application Service: Handles cache invalidation for {$vertical} entities
 */
final readonly class CacheInvalidationService
{
    public function __construct(
        private readonly CacheManager \$cache,
        private readonly LoggerInterface \$logger,
    ) {
    }

    public function invalidateEntityCache(string \$entityId, string \$userId): void
    {
        try {
            // Invalidate entity-specific cache
            \$this->cache->tags(['{$camelVertical}', "{$camelVertical}:\$entityId"])->flush();

            // Invalidate user's entities cache
            \$this->cache->tags(['users', "user:\$userId", "user:\$userId:{$camelVertical}"])->flush();

            \$this->logger->info('Cache invalidated for {$camelVertical} entity', [
                'entity_id' => \$entityId,
                'user_id' => \$userId,
            ]);

        } catch (\Throwable \$e) {
            \$this->logger->error('Failed to invalidate cache for {$camelVertical} entity', [
                'entity_id' => \$entityId,
                'error' => \$e->getMessage(),
            ]);
            // Non-critical error, don't throw
        }
    }

    public function invalidateListingCache(): void
    {
        \$this->cache->tags(["{$camelVertical}_listings"])->flush();
    }

    public function invalidateSearchCache(): void
    {
        \$this->cache->tags(["{$camelVertical}_search"])->flush();
    }
}
PHP;
    }

    private function getEventStoreTestTemplate(string $vertical): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\\{$vertical};

use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Infrastructure\Persistence\EventStore;
use App\Shared\Infrastructure\Persistence\OutboxMessage;
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
        \$event = new Test{$vertical}DomainEvent('test-123', 'user-456');

        \$this->eventStore->publish([\$event]);

        \$this->assertDatabaseHas('outbox_messages', [
            'event_type' => '{$vertical}.test.event',
            'status' => 'pending',
        ]);
    }

    public function test_publishes_multiple_domain_events(): void
    {
        \$events = [
            new Test{$vertical}DomainEvent('test-1', 'user-1'),
            new Test{$vertical}DomainEvent('test-2', 'user-2'),
        ];

        \$this->eventStore->publish(\$events);

        \$this->assertDatabaseCount('outbox_messages', 2);
    }
}

// Test fixture
final class Test{$vertical}DomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string \$testId,
        private readonly string \$userId,
        mixed \$correlationId = null,
    ) {
        parent::__construct(\$correlationId);
    }

    public function eventName(): string
    {
        return '{$vertical}.test.event';
    }

    public function toArray(): array
    {
        return [
            'test_id' => \$this->testId,
            'user_id' => \$this->userId,
        ];
    }
}
PHP;
    }

    private function getApplicationServiceTestTemplate(string $vertical): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\\{$vertical};

use App\Domains\\{$vertical}\Application\Services\\{$vertical}NotificationService;
use Illuminate\Support\Facades\Log;

final class ApplicationServiceTest extends TestCase
{

    private {$vertical}NotificationService \$notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->notificationService = \$this->app->make({$vertical}NotificationService::class);
    }

    public function test_sends_confirmation(): void
    {
        \$this->logger->fake();

        \$this->notificationService->sendConfirmation('entity-123', 'user-456');

        \$this->logger->assertLogged('info', function (\$message, \$context) {
            return \$message === '{$vertical} confirmation sent'
                && \$context['entity_id'] === 'entity-123';
        });
    }
}
PHP;
    }
}
