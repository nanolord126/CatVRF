<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\UserAddress;
use App\Services\UserAddressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserAddressServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAddressService $addressService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addressService = app(UserAddressService::class);
    }

    public function test_add_or_get_address_creates_new_address(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $address = $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
            type: 'home',
            vertical: 'medical',
            tenantId: 1,
        );
        
        $this->assertInstanceOf(UserAddress::class, $address);
        $this->assertEquals($user->id, $address->user_id);
        $this->assertEquals('Москва, ул. Тверская, 1', $address->address);
        $this->assertEquals(1, $address->usage_count);
    }

    public function test_add_or_get_address_reuses_existing(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $address1 = $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
            type: 'home',
        );
        
        $address2 = $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
            type: 'home',
        );
        
        $this->assertEquals($address1->id, $address2->id);
        $this->assertEquals(2, $address2->usage_count);
    }

    public function test_get_address_history_returns_collection(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
        );
        
        $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Арбат, 2',
        );
        
        $history = $this->addressService->getAddressHistory($user->id);
        
        $this->assertCount(2, $history);
    }

    public function test_get_address_history_with_vertical_filter(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
            vertical: 'medical',
        );
        
        $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Арбат, 2',
            vertical: 'delivery',
        );
        
        $medicalHistory = $this->addressService->getAddressHistory($user->id, 'medical');
        
        $this->assertCount(1, $medicalHistory);
        $this->assertEquals('medical', $medicalHistory->first()->vertical);
    }

    public function test_anonymize_address_masks_sensitive_parts(): void
    {
        $address = 'Москва, ул. Тверская, д. 15, кв. 42';
        $anonymized = $this->addressService->anonymizeAddress($address);
        
        $this->assertStringContainsString('***', $anonymized);
        $this->assertNotEquals($address, $anonymized);
    }

    public function test_delete_address_returns_bool(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $address = $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
        );
        
        $deleted = $this->addressService->deleteAddress($user->id, $address->id);
        
        $this->assertTrue($deleted);
        
        $deletedAgain = $this->addressService->deleteAddress($user->id, $address->id);
        $this->assertFalse($deletedAgain);
    }

    public function test_get_address_returns_null_for_non_existent(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $address = $this->addressService->getAddress($user->id, 999);
        
        $this->assertNull($address);
    }

    public function test_get_address_returns_address_for_valid_id(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $created = $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Тверская, 1',
        );
        
        $retrieved = $this->addressService->getAddress($user->id, $created->id);
        
        $this->assertInstanceOf(UserAddress::class, $retrieved);
        $this->assertEquals($created->id, $retrieved->id);
    }

    public function test_max_addresses_limit_respects_config(): void
    {
        $user = \App\Models\User::factory()->create();
        
        // Add 5 addresses (default limit)
        for ($i = 1; $i <= 5; $i++) {
            $this->addressService->addOrGetAddress(
                userId: $user->id,
                address: "Москва, ул. Улица {$i}",
            );
        }
        
        // Add 6th address - should delete oldest
        $this->addressService->addOrGetAddress(
            userId: $user->id,
            address: 'Москва, ул. Новая',
        );
        
        $history = $this->addressService->getAddressHistory($user->id);
        
        // Should still have max 5 addresses
        $this->assertCount(5, $history);
    }
}
