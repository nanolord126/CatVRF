<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\WeddingPlanning;

use PHPUnit\Framework\TestCase;
use App\Domains\WeddingPlanning\Domain\Services\VendorManagementService;

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
            VendorManagementService::class
        );
        $this->assertTrue($reflection->isFinal(), 'VendorManagementService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            VendorManagementService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'VendorManagementService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            VendorManagementService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'VendorManagementService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_register_vendor_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VendorManagementService::class, 'registerVendor'),
            'VendorManagementService must implement registerVendor()'
        );
    }

    public function test_add_review_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VendorManagementService::class, 'addReview'),
            'VendorManagementService must implement addReview()'
        );
    }

    public function test_verify_vendor_method_exists(): void
    {
        $this->assertTrue(
            method_exists(VendorManagementService::class, 'verifyVendor'),
            'VendorManagementService must implement verifyVendor()'
        );
    }
}
