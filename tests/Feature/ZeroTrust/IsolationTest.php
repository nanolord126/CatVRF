<?php

namespace Tests\Feature\ZeroTrust;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::query()->delete();
        User::query()->delete();
    }

    public function test_tenant_isolation_is_enforced()
    {
        $tenant1 = Tenant::create([
            "id" => "t1_" . uniqid(), 
            "name" => "Tenant 1", 
            "type" => "standard",
            "data" => []
        ]);
        $tenant2 = Tenant::create([
            "id" => "t2_" . uniqid(), 
            "name" => "Tenant 2", 
            "type" => "standard",
            "data" => []
        ]);

        tenancy()->initialize($tenant1);
        User::create([
            "name" => "User 1",
            "email" => "u1_" . uniqid() . "@test.com",
            "password" => bcrypt("secret")
        ]);

        tenancy()->initialize($tenant2);
        User::create([
            "name" => "User 2",
            "email" => "u2_" . uniqid() . "@test.com",
            "password" => bcrypt("secret")
        ]);

        tenancy()->initialize($tenant1);
        $this->assertEquals(1, User::count(), "Tenant 1 should only see its own users");
        $this->assertEquals("User 1", User::first()->name);

        tenancy()->initialize($tenant2);
        $this->assertEquals(1, User::count(), "Tenant 2 should only see its own users");
        $this->assertEquals("User 2", User::first()->name);
    }
}
