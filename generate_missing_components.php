<?php
declare(strict_types=1);

/**
 * BATCH GENERATOR: Create missing Events, Contracts, Policies, Migrations, Seeders
 * Usage: php generate_missing_components.php
 */

const BASE_PATH = __DIR__;
const TIMESTAMP = date('Y_m_d_His');

$stats = [
    'events_created' => 0,
    'contracts_created' => 0,
    'policies_created' => 0,
    'migrations_created' => 0,
    'seeders_created' => 0,
    'errors' => [],
];

// Get all Resource names
$resourcePath = BASE_PATH . '/app/Filament/Tenant/Resources';
$resources = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcePath)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filename = $file->getBasename();
        if (preg_match('/^[A-Z][a-zA-Z]+Resource\.php$/', $filename)) {
            $resourceName = str_replace('Resource.php', '', $filename);
            $resources[$resourceName] = true;
        }
    }
}

echo "🔍 Found " . count($resources) . " Resources\n\n";

// 1. CREATE EVENTS
echo "📝 Creating Events...\n";
foreach (array_keys($resources) as $name) {
    $eventFile = BASE_PATH . "/app/Events/{$name}Event.php";
    
    if (!file_exists($eventFile)) {
        $content = generateEventContent($name);
        if (file_put_contents($eventFile, $content)) {
            $stats['events_created']++;
            echo "   ✅ {$name}Event.php\n";
        } else {
            $stats['errors'][] = "Failed to create: $eventFile";
        }
    }
}

// 2. CREATE CONTRACTS
echo "\n📝 Creating Contracts...\n";
foreach (array_keys($resources) as $name) {
    $contractFile = BASE_PATH . "/app/Contracts/{$name}Contract.php";
    
    if (!file_exists($contractFile)) {
        $content = generateContractContent($name);
        if (file_put_contents($contractFile, $content)) {
            $stats['contracts_created']++;
            echo "   ✅ {$name}Contract.php\n";
        } else {
            $stats['errors'][] = "Failed to create: $contractFile";
        }
    }
}

// 3. CREATE POLICIES
echo "\n📝 Creating Policies...\n";
foreach (array_keys($resources) as $name) {
    $policyFile = BASE_PATH . "/app/Policies/{$name}Policy.php";
    
    if (!file_exists($policyFile)) {
        $content = generatePolicyContent($name);
        if (file_put_contents($policyFile, $content)) {
            $stats['policies_created']++;
            echo "   ✅ {$name}Policy.php\n";
        } else {
            $stats['errors'][] = "Failed to create: $policyFile";
        }
    }
}

// 4. CREATE MIGRATIONS
echo "\n📝 Creating Migrations...\n";
foreach (array_keys($resources) as $name) {
    $migrationFile = BASE_PATH . "/database/migrations/" . TIMESTAMP . "_create_{$name}_table.php";
    
    if (!file_exists($migrationFile)) {
        $content = generateMigrationContent($name);
        if (file_put_contents($migrationFile, $content)) {
            $stats['migrations_created']++;
            echo "   ✅ " . basename($migrationFile) . "\n";
        } else {
            $stats['errors'][] = "Failed to create: $migrationFile";
        }
    }
}

// 5. CREATE SEEDERS
echo "\n📝 Creating Seeders...\n";
foreach (array_keys($resources) as $name) {
    $seederFile = BASE_PATH . "/database/seeders/{$name}Seeder.php";
    
    if (!file_exists($seederFile)) {
        $content = generateSeederContent($name);
        if (file_put_contents($seederFile, $content)) {
            $stats['seeders_created']++;
            echo "   ✅ {$name}Seeder.php\n";
        } else {
            $stats['errors'][] = "Failed to create: $seederFile";
        }
    }
}

// Print summary
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "📊 GENERATION SUMMARY\n";
echo str_repeat('=', 80) . "\n";
printf("Events Created:      %3d\n", $stats['events_created']);
printf("Contracts Created:   %3d\n", $stats['contracts_created']);
printf("Policies Created:    %3d\n", $stats['policies_created']);
printf("Migrations Created:  %3d\n", $stats['migrations_created']);
printf("Seeders Created:     %3d\n", $stats['seeders_created']);

if (count($stats['errors']) > 0) {
    echo "\n❌ Errors:\n";
    foreach ($stats['errors'] as $error) {
        echo "   - $error\n";
    }
}

echo "\n✅ Generation Complete!\n";

// ============================================================================
// GENERATORS
// ============================================================================

function generateEventContent(string $name): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * PRODUCTION 2026: {NAME}Event
 * 
 * Fired when a {NAME} record is created, updated, or deleted.
 * Features: Broadcasting support, serialization, correlation tracking.
 */
final class {NAME}Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly string $correlationId;
    public readonly string $action; // 'created', 'updated', 'deleted'
    public readonly ?string $tenantId;
    public readonly ?int $userId;
    public readonly mixed $modelData;

    public function __construct(
        string $action = 'created',
        mixed $modelData = null,
        ?string $tenantId = null,
        ?int $userId = null,
    ) {
        $this->action = $action;
        $this->modelData = $modelData;
        $this->tenantId = $tenantId ?? auth()->user()?->getTenantKey();
        $this->userId = $userId ?? auth()->id();
        $this->correlationId = Str::uuid()->toString();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return '{NAME}.event';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
PHP;
}

function generateContractContent(string $name): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * PRODUCTION 2026: {NAME}Contract
 * 
 * Interface defining {NAME} service contract.
 * Ensures consistent implementation across different contexts.
 */
interface {NAME}Contract
{
    /**
     * Get all {NAME} records with proper tenant scoping
     */
    public function all(string $tenantId): array;

    /**
     * Get {NAME} record by ID with authorization check
     */
    public function getById(int|string $id, string $tenantId, Authenticatable $user): mixed;

    /**
     * Create new {NAME} record with audit logging
     */
    public function create(array $data, string $tenantId, Authenticatable $user, string $correlationId): mixed;

    /**
     * Update {NAME} record with change tracking
     */
    public function update(int|string $id, array $data, string $tenantId, Authenticatable $user, string $correlationId): mixed;

    /**
     * Delete {NAME} record with soft delete and audit trail
     */
    public function delete(int|string $id, string $tenantId, Authenticatable $user, string $correlationId): bool;

    /**
     * Restore soft-deleted {NAME} record
     */
    public function restore(int|string $id, string $tenantId, Authenticatable $user, string $correlationId): bool;
}
PHP;
}

function generatePolicyContent(string $name): string
{
    return <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * PRODUCTION 2026: {NAME}Policy
 * 
 * Authorization policy for {NAME} resource.
 * Controls access to view, create, update, delete operations.
 */
final class {NAME}Policy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any {NAME} records
     */
    public function viewAny(User $user): Response|bool
    {
        return $user->getTenantKey() !== null;
    }

    /**
     * Determine if user can view {NAME} record
     */
    public function view(User $user, mixed $model): Response|bool
    {
        return $user->getTenantKey() === $model->tenant_id ?? true;
    }

    /**
     * Determine if user can create {NAME} record
     */
    public function create(User $user): Response|bool
    {
        return $user->getTenantKey() !== null;
    }

    /**
     * Determine if user can update {NAME} record
     */
    public function update(User $user, mixed $model): Response|bool
    {
        return $user->getTenantKey() === $model->tenant_id ?? true;
    }

    /**
     * Determine if user can delete {NAME} record
     */
    public function delete(User $user, mixed $model): Response|bool
    {
        return $user->getTenantKey() === $model->tenant_id ?? true;
    }

    /**
     * Determine if user can permanently delete {NAME} record
     */
    public function forceDelete(User $user, mixed $model): Response|bool
    {
        return $user->isSuperAdmin() && $user->getTenantKey() === $model->tenant_id ?? true;
    }

    /**
     * Determine if user can restore {NAME} record
     */
    public function restore(User $user, mixed $model): Response|bool
    {
        return $user->getTenantKey() === $model->tenant_id ?? true;
    }
}
PHP;
}

function generateMigrationContent(string $name): string
{
    $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    
    return <<<PHP
<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRODUCTION 2026: Create {$name} Table
 * 
 * Multi-tenant table with audit logging and soft deletes support.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->uuid('tenant_id')->index();
            \$table->string('correlation_id')->nullable()->index();
            
            // Add columns here
            
            // Audit columns
            \$table->unsignedBigInteger('created_by')->nullable();
            \$table->unsignedBigInteger('updated_by')->nullable();
            \$table->string('updated_reason')->nullable();
            
            // Timestamps & soft delete
            \$table->timestamps();
            \$table->softDeletes();
            
            // Indexes
            \$table->index(['tenant_id', 'created_at']);
            \$table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            \$table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
}

function generateSeederContent(string $name): string
{
    $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    
    return <<<PHP
<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PRODUCTION 2026: {$name}Seeder
 * 
 * Seed {$tableName} table with test data.
 * Includes multi-tenant scoping and realistic data.
 */
final class {$name}Seeder extends Seeder
{
    public function run(): void
    {
        // Get first tenant
        \$tenantId = DB::table('tenants')->first()?->id ?? Str::uuid()->toString();
        
        DB::table('{$tableName}')->insertOrIgnore([
            [
                'tenant_id' => \$tenantId,
                'correlation_id' => Str::uuid()->toString(),
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
PHP;
}

// Replace {NAME} placeholders
function replacePlaceholders(string $content, string $name): string
{
    return str_replace('{NAME}', $name, $content);
}
