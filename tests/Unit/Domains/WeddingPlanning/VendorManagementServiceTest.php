<?php declare(strict_types=1);

namespace Tests\Unit\Domains\WeddingPlanning;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VendorManagementService.
 *
 * @covers \App\Domains\WeddingPlanning\Domain\Services\VendorManagementService
 */
final class VendorManagementServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class
        );
        $this->assertTrue($reflection->isFinal(), 'VendorManagementService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'VendorManagementService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'VendorManagementService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_registerVendor_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class, 'registerVendor'),
            'VendorManagementService must implement registerVendor()'
        );
    }

    public function test_addReview_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class, 'addReview'),
            'VendorManagementService must implement addReview()'
        );
    }

    public function test_verifyVendor_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\VendorManagementService::class, 'verifyVendor'),
            'VendorManagementService must implement verifyVendor()'
        );
    }

}
