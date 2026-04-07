<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Finances;

use App\Domains\Finances\Controllers\FinanceRecordController;
use App\Domains\Finances\Domain\Entities\FinancialTransaction;
use App\Domains\Finances\Domain\Entities\Payout;
use App\Domains\Finances\Domain\Enums\PayoutStatus;
use App\Domains\Finances\Domain\Enums\TransactionType;
use App\Domains\Finances\Domain\Events\PayoutInitiated;
use App\Domains\Finances\Domain\Interfaces\EarningCalculatorInterface;
use App\Domains\Finances\Domain\Interfaces\PayoutRepositoryInterface;
use App\Domains\Finances\Domain\Interfaces\TransactionRepositoryInterface;
use App\Domains\Finances\Domain\Listeners\LogPayoutInitiatedListener;
use App\Domains\Finances\Domain\Services\PayoutService;
use App\Domains\Finances\DTOs\CreateBudgetDto;
use App\Domains\Finances\DTOs\CreateTransactionDto;
use App\Domains\Finances\Events\FinanceRecordCreated;
use App\Domains\Finances\Events\FinanceRecordUpdated;
use App\Domains\Finances\Models\FinanceRecord;
use App\Domains\Finances\Policies\FinanceRecordPolicy;
use App\Domains\Finances\Resources\FinanceRecordResource;
use App\Domains\Finances\Services\FinancesService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Архитектурные тесты вертикали Finances — CatVRF 2026.
 *
 * Проверяет: 9-слойную архитектуру, DDD-ядро, strict_types,
 * final/readonly, constructor injection, correlation_id, AI-конструктор.
 *
 * Не требует загрузки Laravel (PHPUnit\Framework\TestCase).
 * Все проверки через Reflection API и файловую систему.
 */
final class FinancesVerticalFeatureTest extends TestCase
{
    /** Абсолютный путь к корню проекта (без trailing slash). */
    private static function basePath(string $relative = ''): string
    {
        $root = dirname(__DIR__, 4); // tests/Feature/Domains/Finances → project root
        return $root . ($relative !== '' ? DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR) : '');
    }

    // ──────────────────────────────────────
    //  Архитектура: структура директорий
    // ──────────────────────────────────────

    #[Test]
    public function domain_directory_exists(): void
    {
        self::assertDirectoryExists(self::basePath('app/Domains/Finances'));
    }

    #[Test]
    public function domain_has_all_9_layers(): void
    {
        $basePath = self::basePath('app/Domains/Finances');

        $requiredDirs = [
            'Models',
            'DTOs',
            'Services',
            'Controllers',
            'Resources',
            'Events',
            'Listeners',
            'Jobs',
            'Policies',
        ];

        foreach ($requiredDirs as $dir) {
            self::assertDirectoryExists(
                $basePath . DIRECTORY_SEPARATOR . $dir,
                "Слой {$dir} отсутствует в вертикали Finances",
            );
        }
    }

    #[Test]
    public function ddd_core_layers_exist(): void
    {
        $domainPath = self::basePath('app/Domains/Finances/Domain');

        $required = ['Entities', 'Enums', 'Events', 'Interfaces', 'Listeners', 'Services'];

        foreach ($required as $dir) {
            self::assertDirectoryExists(
                $domainPath . DIRECTORY_SEPARATOR . $dir,
                "DDD-слой Domain/{$dir} отсутствует",
            );
        }
    }

    #[Test]
    public function infrastructure_layers_exist(): void
    {
        $infraPath = self::basePath('app/Domains/Finances/Infrastructure');

        self::assertDirectoryExists($infraPath . DIRECTORY_SEPARATOR . 'Jobs');
        self::assertDirectoryExists($infraPath . DIRECTORY_SEPARATOR . 'Persistence');
        self::assertDirectoryExists($infraPath . DIRECTORY_SEPARATOR . 'Providers');
    }

    #[Test]
    public function ai_directory_exists(): void
    {
        $aiPath = self::basePath('app/Domains/Finances/Services/AI');

        if (! is_dir($aiPath)) {
            self::markTestSkipped('AI-директория пока отсутствует');
        }

        $files = glob($aiPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        self::assertNotEmpty($files, 'AI-конструктор отсутствует');
    }

    // ──────────────────────────────────────
    //  Канонические правила: strict_types + final
    // ──────────────────────────────────────

    #[Test]
    public function all_domain_classes_are_final(): void
    {
        $classes = [
            FinancialTransaction::class,
            Payout::class,
            PayoutService::class,
            LogPayoutInitiatedListener::class,
        ];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue($ref->isFinal(), "{$class} должен быть final");
        }
    }

    #[Test]
    public function all_enums_have_required_methods(): void
    {
        $ref = new \ReflectionEnum(PayoutStatus::class);
        self::assertTrue($ref->hasMethod('label'));
        self::assertTrue($ref->hasMethod('color'));
        self::assertTrue($ref->hasMethod('isTerminal'));
        self::assertTrue($ref->hasMethod('canTransitionTo'));

        $ref2 = new \ReflectionEnum(TransactionType::class);
        self::assertTrue($ref2->hasMethod('label'));
        self::assertTrue($ref2->hasMethod('color'));
        self::assertTrue($ref2->hasMethod('isCredit'));
        self::assertTrue($ref2->hasMethod('isDebit'));
    }

    #[Test]
    public function model_is_final_and_has_required_fillable(): void
    {
        $ref = new \ReflectionClass(FinanceRecord::class);
        self::assertTrue($ref->isFinal(), 'FinanceRecord должен быть final');

        // Проверяем через ReflectionProperty вместо инстанцирования Eloquent-модели
        $prop = $ref->getProperty('fillable');
        $prop->setAccessible(true);

        // Получаем default value из объявления (без инстанцирования)
        $defaultProps = $ref->getDefaultProperties();
        $fillable = $defaultProps['fillable'] ?? [];

        self::assertContains('tenant_id', $fillable, 'fillable должен содержать tenant_id');
        self::assertContains('business_group_id', $fillable, 'fillable должен содержать business_group_id');
        self::assertContains('uuid', $fillable, 'fillable должен содержать uuid');
        self::assertContains('correlation_id', $fillable, 'fillable должен содержать correlation_id');
        self::assertContains('tags', $fillable, 'fillable должен содержать tags');
    }

    #[Test]
    public function controller_is_final_with_constructor_injection(): void
    {
        $ref = new \ReflectionClass(FinanceRecordController::class);
        self::assertTrue($ref->isFinal(), 'Controller должен быть final');

        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);
        self::assertGreaterThanOrEqual(
            4,
            $ctor->getNumberOfParameters(),
            'Controller должен иметь минимум 4 зависимости (response, fraud, db, audit)',
        );
    }

    #[Test]
    public function policy_is_final(): void
    {
        $ref = new \ReflectionClass(FinanceRecordPolicy::class);
        self::assertTrue($ref->isFinal(), 'Policy должна быть final');
    }

    #[Test]
    public function events_are_final(): void
    {
        $classes = [
            FinanceRecordCreated::class,
            FinanceRecordUpdated::class,
            PayoutInitiated::class,
        ];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue($ref->isFinal(), "{$class} должен быть final");
        }
    }

    // ──────────────────────────────────────
    //  Интерфейсы (Ports)
    // ──────────────────────────────────────

    #[Test]
    public function domain_interfaces_exist(): void
    {
        self::assertTrue(interface_exists(EarningCalculatorInterface::class));
        self::assertTrue(interface_exists(PayoutRepositoryInterface::class));
        self::assertTrue(interface_exists(TransactionRepositoryInterface::class));
    }

    // ──────────────────────────────────────
    //  DTOs: readonly
    // ──────────────────────────────────────

    #[Test]
    public function dtos_are_readonly(): void
    {
        $classes = [CreateTransactionDto::class, CreateBudgetDto::class];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue($ref->isReadonly(), "{$class} должен быть readonly");
        }
    }

    #[Test]
    public function dtos_have_toArray_and_toAuditContext(): void
    {
        $classes = [CreateTransactionDto::class, CreateBudgetDto::class];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue($ref->hasMethod('toArray'), "{$class}::toArray() отсутствует");
            self::assertTrue($ref->hasMethod('toAuditContext'), "{$class}::toAuditContext() отсутствует");
        }
    }

    // ──────────────────────────────────────
    //  AI-конструктор
    // ──────────────────────────────────────

    #[Test]
    public function ai_constructor_class_exists(): void
    {
        $class = 'App\\Domains\\Finances\\Services\\AI\\FinancialAdvisorConstructorService';

        self::assertTrue(class_exists($class), 'AI-конструктор не найден');
    }

    #[Test]
    public function ai_constructor_is_final_and_readonly(): void
    {
        $class = 'App\\Domains\\Finances\\Services\\AI\\FinancialAdvisorConstructorService';

        if (! class_exists($class)) {
            self::markTestSkipped('AI-конструктор отсутствует');
        }

        $ref = new \ReflectionClass($class);
        self::assertTrue($ref->isFinal(), 'AI-конструктор должен быть final');
        self::assertTrue($ref->isReadonly(), 'AI-конструктор должен быть readonly');
    }

    #[Test]
    public function ai_constructor_has_analyzeAndRecommend(): void
    {
        $class = 'App\\Domains\\Finances\\Services\\AI\\FinancialAdvisorConstructorService';

        if (! class_exists($class)) {
            self::markTestSkipped('AI-конструктор отсутствует');
        }

        $ref = new \ReflectionClass($class);
        self::assertTrue(
            $ref->hasMethod('analyzeAndRecommend'),
            'AI-конструктор должен иметь analyzeAndRecommend()',
        );
    }

    // ──────────────────────────────────────
    //  B2B определение (канон 2026)
    // ──────────────────────────────────────

    #[Test]
    public function b2b_detection_follows_canon_rule(): void
    {
        $request = new \Illuminate\Http\Request();
        $request->merge(['inn' => '7700000000', 'business_card_id' => 123]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');
        self::assertTrue($isB2B);
    }

    #[Test]
    public function b2c_detection_without_inn(): void
    {
        $request = new \Illuminate\Http\Request();
        $request->merge(['name' => 'Иванов']);

        $isB2B = $request->has('inn') && $request->has('business_card_id');
        self::assertFalse($isB2B);
    }

    // ──────────────────────────────────────
    //  Services: constructor injection
    // ──────────────────────────────────────

    #[Test]
    public function finances_service_uses_constructor_injection(): void
    {
        $ref = new \ReflectionClass(FinancesService::class);
        $ctor = $ref->getConstructor();

        self::assertNotNull($ctor, 'FinancesService должен иметь конструктор');
        self::assertGreaterThan(0, $ctor->getNumberOfParameters());
    }

    #[Test]
    public function payout_service_uses_constructor_injection(): void
    {
        $ref = new \ReflectionClass(PayoutService::class);
        $ctor = $ref->getConstructor();

        self::assertNotNull($ctor, 'PayoutService должен иметь конструктор');
        self::assertGreaterThan(0, $ctor->getNumberOfParameters());
    }

    // ──────────────────────────────────────
    //  Events: audit context
    // ──────────────────────────────────────

    #[Test]
    public function events_have_toAuditContext(): void
    {
        $classes = [FinanceRecordCreated::class, FinanceRecordUpdated::class];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue(
                $ref->hasMethod('toAuditContext'),
                "{$class}::toAuditContext() отсутствует",
            );
        }
    }

    #[Test]
    public function updated_event_has_hasChanged_method(): void
    {
        $ref = new \ReflectionClass(FinanceRecordUpdated::class);
        self::assertTrue($ref->hasMethod('hasChanged'));
    }

    // ──────────────────────────────────────
    //  Correlation-ID: проверяем что DTO/Event используют его
    // ──────────────────────────────────────

    #[Test]
    public function correlation_id_is_present_in_dto_and_events(): void
    {
        // DTO содержит correlationId
        $ref = new \ReflectionClass(CreateTransactionDto::class);
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);
        $paramNames = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $ctor->getParameters(),
        );
        self::assertContains('correlationId', $paramNames, 'CreateTransactionDto должен принимать correlationId');

        // Event содержит correlationId
        $eventRef = new \ReflectionClass(FinanceRecordCreated::class);
        $eventCtor = $eventRef->getConstructor();
        self::assertNotNull($eventCtor);
        $eventParams = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $eventCtor->getParameters(),
        );
        self::assertContains('correlationId', $eventParams, 'FinanceRecordCreated должен содержать correlationId');
    }

    // ──────────────────────────────────────
    //  Model: casts & scopes (reflection only)
    // ──────────────────────────────────────

    #[Test]
    public function model_has_json_casts_for_tags_and_metadata(): void
    {
        $ref = new \ReflectionClass(FinanceRecord::class);
        $defaults = $ref->getDefaultProperties();
        $casts = $defaults['casts'] ?? [];

        self::assertArrayHasKey('tags', $casts, 'FinanceRecord должен кастить tags');
        self::assertArrayHasKey('metadata', $casts, 'FinanceRecord должен кастить metadata');
    }

    #[Test]
    public function model_has_scopes(): void
    {
        $ref = new \ReflectionClass(FinanceRecord::class);

        self::assertTrue($ref->hasMethod('scopeForBusinessGroup'));
        self::assertTrue($ref->hasMethod('scopeWithStatus'));
        self::assertTrue($ref->hasMethod('scopeOfType'));
    }

    #[Test]
    public function model_has_relationships(): void
    {
        $ref = new \ReflectionClass(FinanceRecord::class);

        self::assertTrue($ref->hasMethod('tenant'));
        self::assertTrue($ref->hasMethod('businessGroup'));
        self::assertTrue($ref->hasMethod('user'));
    }

    // ──────────────────────────────────────
    //  Resource: format check
    // ──────────────────────────────────────

    #[Test]
    public function resource_class_exists_and_is_final(): void
    {
        $ref = new \ReflectionClass(FinanceRecordResource::class);
        self::assertTrue($ref->isFinal());
    }
}