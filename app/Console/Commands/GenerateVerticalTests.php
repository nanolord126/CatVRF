<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateVerticalTests extends Command
{
    protected $signature = 'vertical-tests:generate {vertical? : Specific vertical name}';
    protected $description = 'Generate unit and feature tests for all verticals';

    private array $verticals = [
        // Priority 1: Critical Business Verticals
        'Healthcare' => ['HealthcareService', 'AppointmentService', 'MedicalRecordService', 'DoctorService'],
        'BeautyMasters' => ['AppointmentService', 'MasterScheduleService', 'BonusService', 'BeautyCertificationService'],
        'RealEstate' => ['PropertyService', 'BookingService', 'ListingService', 'AgentService'],
        'Restaurant' => ['OrderService', 'ReservationService', 'MenuService', 'TableService'],
        'Taxi' => ['TaxiService', 'DriverService', 'RouteService', 'RideService'],
        'Hotels' => ['HotelService', 'BookingService', 'RoomService', 'GuestService'],
        
        // Priority 2: High-Value Verticals
        'Fashion' => ['FashionService', 'ProductService', 'RecommendationService', 'InventoryService'],
        'Dental' => ['DentalService', 'TreatmentPlanService', 'LabService', 'AppointmentService'],
        'Fitness' => ['FitnessService', 'MembershipService', 'ClassService', 'AttendanceService'],
        'Pharmacy' => ['PharmacyService', 'PrescriptionService', 'OrderService', 'InventoryService'],
        'Veterinary' => ['VeterinaryService', 'AppointmentService', 'PetService', 'TreatmentService'],
        
        // Priority 3: Service Verticals
        'Auto' => ['AutoService', 'DiagnosticService', 'RepairService'],
        'Education' => ['CourseService', 'EnrollmentService', 'LessonService'],
        'Insurance' => ['InsuranceService', 'PolicyService', 'ClaimService'],
        'Legal' => ['LegalService', 'ConsultationService', 'DocumentService'],
        'Logistics' => ['LogisticsService', 'ShipmentService', 'TrackingService'],
        
        // Priority 4: Other Verticals
        'Art', 'BooksAndLiterature', 'CarRental', 'CleaningServices', 'Collectibles',
        'Communication', 'Confectionery', 'Construction', 'Consulting', 'Content',
        'Delivery', 'Electronics', 'EventPlanning', 'Freelance', 'Gardening',
        'Gifts', 'Grocery', 'HealthyFood', 'HomeServices', 'Household',
        'Jewelry', 'Luxury', 'Media', 'Music', 'OfficeCatering',
        'PartySupplies', 'Pet', 'Photography', 'Sports', 'SportsNutrition',
        'Tickets', 'Toys', 'Travel', 'Vegan', 'Video',
    ];

    public function handle(): int
    {
        $specificVertical = $this->argument('vertical');

        if ($specificVertical) {
            return $this->generateForVertical($specificVertical);
        }

        $this->info('Generating tests for all verticals...');
        $totalTests = 0;

        foreach ($this->verticals as $vertical => $services) {
            if (is_array($services)) {
                $this->info("Processing vertical: {$vertical}");
                foreach ($services as $service) {
                    $totalTests += $this->generateTestFiles($vertical, $service);
                }
            } else {
                $totalTests += $this->generateTestFiles($vertical, $vertical . 'Service');
            }
        }

        $this->info("✅ Generated {$totalTests} test files for all verticals");
        return Command::SUCCESS;
    }

    private function generateForVertical(string $vertical): int
    {
        $this->info("Generating tests for vertical: {$vertical}");

        if (!isset($this->verticals[$vertical])) {
            $this->error("Vertical '{$vertical}' not found in configuration");
            return Command::FAILURE;
        }

        $services = $this->verticals[$vertical];
        $totalTests = 0;

        if (is_array($services)) {
            foreach ($services as $service) {
                $totalTests += $this->generateTestFiles($vertical, $service);
            }
        } else {
            $totalTests += $this->generateTestFiles($vertical, $vertical . 'Service');
        }

        $this->info("✅ Generated {$totalTests} test files for {$vertical}");
        return Command::SUCCESS;
    }

    private function generateTestFiles(string $vertical, string $service): int
    {
        $count = 0;

        // Generate Unit Test
        if ($this->generateUnitTest($vertical, $service)) {
            $count++;
            $this->line("  ✓ Unit test: {$service}Test");
        }

        // Generate Feature Test
        if ($this->generateFeatureTest($vertical, $service)) {
            $count++;
            $this->line("  ✓ Feature test: {$service}ApiTest");
        }

        return $count;
    }

    private function generateUnitTest(string $vertical, string $service): bool
    {
        $namespace = str_replace('/', '\\', $vertical);
        $testPath = base_path("tests/Unit/Verticals/{$vertical}/{$service}Test.php");

        if (File::exists($testPath)) {
            return false;
        }

        $content = $this->getUnitTestTemplate($namespace, $service);
        File::ensureDirectoryExists(dirname($testPath));
        File::put($testPath, $content);

        return true;
    }

    private function generateFeatureTest(string $vertical, string $service): bool
    {
        $namespace = str_replace('/', '\\', $vertical);
        $testPath = base_path("tests/Feature/Verticals/{$vertical}/{$service}ApiTest.php");

        if (File::exists($testPath)) {
            return false;
        }

        $content = $this->getFeatureTestTemplate($namespace, $service, $vertical);
        File::ensureDirectoryExists(dirname($testPath));
        File::put($testPath, $content);

        return true;
    }

    private function getUnitTestTemplate(string $namespace, string $service): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Unit\Verticals\\{$namespace};

use Tests\BaseVerticalTestCase;
use Modules\\{$namespace}\\Services\\{$service};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;

final class {$service}Test extends BaseVerticalTestCase
{
    use RefreshDatabase;

    private {$service} \$service;

    protected function setUp(): void
    {
        parent::setUp();
        
        \$this->service = app({$service}::class);
    }

    public function test_service_can_be_instantiated(): void
    {
        \$this->assertInstanceOf({$service}::class, \$this->service);
    }

    public function test_create_operation_succeeds(): void
    {
        // Arrange
        \$data = \$this->getValidTestData();

        // Act
        \$result = \$this->service->create(\$data);

        // Assert
        \$this->assertNotNull(\$result);
        \$this->assertDatabaseHas(\$this->getTableName(), [
            'id' => \$result->id,
        ]);
    }

    public function test_update_operation_succeeds(): void
    {
        // Arrange
        \$entity = \$this->createTestEntity();
        \$updateData = ['name' => 'Updated Name'];

        // Act
        \$result = \$this->service->update(\$entity->id, \$updateData);

        // Assert
        \$this->assertEquals('Updated Name', \$result->name);
    }

    public function test_delete_operation_succeeds(): void
    {
        // Arrange
        \$entity = \$this->createTestEntity();

        // Act
        \$this->service->delete(\$entity->id);

        // Assert
        \$this->assertDatabaseMissing(\$this->getTableName(), [
            'id' => \$entity->id,
        ]);
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        \$this->expectException(\InvalidArgumentException::class);
        
        \$this->service->create([]);
    }

    public function test_fraud_check_before_mutation(): void
    {
        // Arrange
        \$data = \$this->getValidTestData();

        // Act
        \$result = \$this->service->create(\$data);

        // Assert - fraud check should have been performed
        \$this->assertNotNull(\$result);
    }

    public function test_audit_logging_on_operations(): void
    {
        // Arrange
        \$data = \$this->getValidTestData();

        // Act
        \$this->service->create(\$data);

        // Assert
        \$this->assertAuditLogExists('created', get_class(\$this->service), 1);
    }

    private function getValidTestData(): array
    {
        return [
            'name' => 'Test Entity',
            'status' => 'active',
        ];
    }

    private function createTestEntity()
    {
        return \$this->service->create(\$this->getValidTestData());
    }

    private function getTableName(): string
    {
        return Str::snake(Str::plural(class_basename(\$this->service)));
    }
}
PHP;
    }

    private function getFeatureTestTemplate(string $namespace, string $service, string $vertical): string
    {
        $verticalSlug = strtolower($vertical);
        return <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Feature\Verticals\\{$namespace};

use Tests\BaseVerticalTestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class {$service}ApiTest extends BaseVerticalTestCase
{
    use RefreshDatabase;

    private User \$user;

    protected function setUp(): void
    {
        parent::setUp();
        
        \$this->user = User::factory()->create();
        \$this->actingAs(\$this->user);
    }

    public function test_index_endpoint_returns_list(): void
    {
        \$response = \$this->getJson('/api/v1/{$verticalSlug}');

        \$response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_show_endpoint_returns_single_item(): void
    {
        \$item = \$this->createTestItem();

        \$response = \$this->getJson("/api/v1/{$verticalSlug}/{\$item->id}");

        \$response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => \$item->id,
                ],
            ]);
    }

    public function test_store_endpoint_creates_item(): void
    {
        \$data = [
            'name' => 'Test Item',
            'description' => 'Test Description',
        ];

        \$response = \$this->postJson("/api/v1/{$verticalSlug}", \$data);

        \$response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name'],
            ]);
    }

    public function test_store_validation_requires_name(): void
    {
        \$response = \$this->postJson("/api/v1/{$verticalSlug}", [
            'description' => 'Test Description',
        ]);

        \$response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_endpoint_modifies_item(): void
    {
        \$item = \$this->createTestItem();

        \$response = \$this->putJson("/api/v1/{$verticalSlug}/{\$item->id}", [
            'name' => 'Updated Name',
        ]);

        \$response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);
    }

    public function test_delete_endpoint_removes_item(): void
    {
        \$item = \$this->createTestItem();

        \$response = \$this->deleteJson("/api/v1/{$verticalSlug}/{\$item->id}");

        \$response->assertStatus(204);
    }

    public function test_unauthorized_access_returns_401(): void
    {
        auth()->logout();

        \$response = \$this->getJson("/api/v1/{$verticalSlug}");

        \$response->assertStatus(401);
    }

    public function test_pagination_works_correctly(): void
    {
        \$this->createTestItem();
        \$this->createTestItem();
        \$this->createTestItem();

        \$response = \$this->getJson("/api/v1/{$verticalSlug}?per_page=2");

        \$response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'per_page' => 2,
                    'total' => 3,
                ],
            ]);
    }

    public function test_filtering_by_status(): void
    {
        \$this->createTestItem(['status' => 'active']);
        \$this->createTestItem(['status' => 'inactive']);

        \$response = \$this->getJson("/api/v1/{$verticalSlug}?status=active");

        \$response->assertStatus(200);

        \$items = \$response->json('data');
        \$this->assertCount(1, \$items);
        \$this->assertEquals('active', \$items[0]['status']);
    }

    private function createTestItem(array \$attributes = [])
    {
        // Implement factory or create logic
        return null;
    }
}
PHP;
    }
}
