<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Jobs;

use App\Domains\Payment\Jobs\ProcessPaymentRecordJob;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты для ProcessPaymentRecordJob.
 */
final class ProcessPaymentRecordJobTest extends TestCase
{
    public function test_job_is_final(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_job_implements_should_queue(): void
    {
        $this->assertTrue(
            is_subclass_of(ProcessPaymentRecordJob::class, ShouldQueue::class)
            || in_array(ShouldQueue::class, class_implements(ProcessPaymentRecordJob::class)),
        );
    }

    public function test_job_has_tries_property(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $this->assertTrue($ref->hasProperty('tries'));

        $prop = $ref->getProperty('tries');
        $instance = $ref->newInstanceWithoutConstructor();
        $prop->setAccessible(true);
        $this->assertSame(3, $prop->getValue($instance));
    }

    public function test_job_has_backoff_property(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $this->assertTrue($ref->hasProperty('backoff'));

        $prop = $ref->getProperty('backoff');
        $instance = $ref->newInstanceWithoutConstructor();
        $prop->setAccessible(true);
        $this->assertSame(60, $prop->getValue($instance));
    }

    public function test_job_has_queue_set_to_payments(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $instance = $ref->newInstanceWithoutConstructor();

        // Задаём свойство queue через onQueue() в конструкторе — но без конструктора
        // Используем Queueable trait property
        $prop = $ref->getProperty('queue');
        $prop->setAccessible(true);

        // Создадим экземпляр с конструктором
        $job = new ProcessPaymentRecordJob(1, 'corr-test');
        $this->assertSame('payments', $prop->getValue($job));
    }

    public function test_job_has_handle_method(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $this->assertTrue($ref->hasMethod('handle'));
    }

    public function test_job_handle_accepts_deps_not_constructor(): void
    {
        // LoggerInterface и AuditService — в handle(), НЕ в конструкторе
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);

        $handleParams = $ref->getMethod('handle')->getParameters();
        $handleParamTypes = array_map(
            fn(\ReflectionParameter $p) => $p->getType()?->getName(),
            $handleParams,
        );

        // handle() должен принимать LoggerInterface и/или AuditService
        $this->assertTrue(
            in_array(LoggerInterface::class, $handleParamTypes) || in_array(AuditService::class, $handleParamTypes),
            'handle() must accept deps via method injection',
        );

        // Конструктор НЕ должен принимать LoggerInterface
        $ctor = $ref->getConstructor();
        if ($ctor !== null) {
            $ctorParamTypes = array_map(
                fn(\ReflectionParameter $p) => $p->getType()?->getName(),
                $ctor->getParameters(),
            );
            $this->assertNotContains(
                LoggerInterface::class,
                $ctorParamTypes,
                'LoggerInterface must NOT be in Job constructor (breaks serialization)',
            );
        }
    }

    public function test_job_has_failed_method(): void
    {
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $this->assertTrue($ref->hasMethod('failed'));

        $method = $ref->getMethod('failed');
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(1, count($params));
    }

    public function test_job_is_serialization_safe(): void
    {
        // У Job не должно быть не-сериализуемых зависимостей в конструкторе
        $ref = new \ReflectionClass(ProcessPaymentRecordJob::class);
        $ctor = $ref->getConstructor();

        if ($ctor !== null) {
            foreach ($ctor->getParameters() as $param) {
                $type = $param->getType()?->getName();
                // Интерфейсы сервисов НЕ должны быть в конструкторе
                $this->assertNotSame(LoggerInterface::class, $type, 'Logger breaks serialization');
                $this->assertNotSame(AuditService::class, $type, 'AuditService breaks serialization');
                $this->assertNotSame(FraudControlService::class, $type ?? '', 'FraudControlService breaks serialization');
            }
        }

        $this->assertTrue(true);
    }

    public function test_job_no_facade_imports(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Jobs/ProcessPaymentRecordJob.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
        $this->assertStringNotContainsString('Log::', $src);
    }

    public function test_job_has_strict_types(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Jobs/ProcessPaymentRecordJob.php');
        $this->assertStringContainsString('declare(strict_types=1);', $src);
    }
}
