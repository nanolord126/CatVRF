<?php declare(strict_types=1);

namespace App\Domains\Education\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PremiumEducationTest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use RefreshDatabase;

        private User $user;
        private Course $course;
        private string $correlationId;

        /**
         * Предустановка окружения (Setup).
         */
        protected function setUp(): void
        {
            parent::setUp();

            $this->correlationId = (string) Str::uuid();

            // 1. Создание Теннанта Провайдера
            $this->providerTenant = Tenant::factory()->create(['name' => 'EDU Provider Academy']);

            // 2. Создание Теннанта Клиента (B2B)
            $this->clientTenant = Tenant::factory()->create(['name' => 'Corporate Client Corp']);

            // 3. Студент (B2C)
            $this->user = User::factory()->create(['tenant_id' => $this->clientTenant->id]);

            // 4. Курс (Premium)
            $this->course = Course::factory()->create([
                'tenant_id' => $this->providerTenant->id,
                'title' => 'AI Architecture 2026 Masterclass',
                'price_kopecks' => 500000, // 5000 ₽
                'status' => 'published',
                'level' => 'expert',
            ]);
        }

        /**
         * Тест B2C: Прямая покупка и AI путь.
         */
        public function test_b2c_direct_enrollment_with_ai_path(): void
        {
            Log::info('Test B2C: Direct enrollment started');

            $service = app(EducationManagementService::class);
            $enrollment = $service->enrollUserDirectly($this->user, $this->course, $this->correlationId);

            // Assertions: Зачисление создано
            $this->assertDatabaseHas('enrollments', [
                'uuid' => $enrollment->uuid,
                'user_id' => $this->user->id,
                'course_id' => $this->course->id,
                'status' => 'active',
                'correlation_id' => $this->correlationId,
            ]);

            // Assertions: AI траектория существует
            $this->assertNotNull($enrollment->ai_path);
            $this->assertEquals($this->user->id, $enrollment->ai_path['user_id']);
            $this->assertArrayHasKey('modules', $enrollment->ai_path);
        }

        /**
         * Тест B2B: Потребление слотов корпоративного контракта.
         */
        public function test_b2b_corporate_slot_consumption(): void
        {
            Log::info('Test B2B: Slot consumption started');

            // 1. Создание B2B контракта (3 слота)
            $contract = CorporateContract::factory()->create([
                'provider_tenant_id' => $this->providerTenant->id,
                'client_tenant_id' => $this->clientTenant->id,
                'slots_total' => 3,
                'slots_available' => 3,
                'status' => 'active',
            ]);

            $service = app(EducationManagementService::class);

            // 2. Студент записывается через B2B контракт
            $enrollment = $service->enrollUserUnderContract($this->user, $contract, $this->course, $this->correlationId);

            // Assertions: Слоты уменьшились
            $contract->refresh();
            $this->assertEquals(2, $contract->slots_available);

            // Assertions: Зачисление связано с контрактом
            $this->assertEquals($contract->id, $enrollment->corporate_contract_id);

            $this->assertDatabaseHas('enrollments', [
                'uuid' => $enrollment->uuid,
                'corporate_contract_id' => $contract->id,
            ]);
        }

        /**
         * Тест API: Попытка зачисления без Correlation ID должна упасть (RBAC 2026).
         */
        public function test_api_requires_correlation_id_security(): void
        {
            // Канон 2026: ทุก запрос к критическим эндпоинтам требует Correlation ID
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/education/enroll', [
                    'course_uuid' => $this->course->uuid,
                ]);

            // Assert: 422 Unprocessable Entity (Correlation ID required)
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['correlation_id']);
        }

        /**
         * Тест Tenant Scoping: Обучающий Провайдер видит только свои контракты.
         */
        public function test_tenant_isolation_education_contracts(): void
        {
            // Контракт другого теннанта
            $otherTenant = Tenant::factory()->create();
            CorporateContract::factory()->create([
                'provider_tenant_id' => $otherTenant->id,
                'client_tenant_id' => $otherTenant->id,
            ]);

            // Наш провайдер не должен видеть чужих секретных контрактов
            $this->actingAs($this->user); // У студента tenant_id = clientTenant

            $visibleContracts = CorporateContract::all(); // С global scope

            foreach ($visibleContracts as $c) {
                $this->assertTrue(
                    $c->provider_tenant_id === $this->clientTenant->id ||
                    $c->client_tenant_id === $this->clientTenant->id
                );
            }
        }
}
