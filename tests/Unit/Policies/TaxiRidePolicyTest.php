<?php

namespace Tests\Unit\Domains\Taxi\Policies;

use App\Models\Domains\Taxi\TaxiRide;
use App\Models\Domains\Taxi\Policies\TaxiRidePolicy;
use App\Models\User;
use Tests\TestCase;

class TaxiRidePolicyTest extends TestCase
{
    protected $policy;
    protected $user;
    protected $taxiRide;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TaxiRidePolicy();
        $this->user = User::factory()->create();
        $this->taxiRide = TaxiRide::factory()->create(['tenant_id' => tenant()->id]);
    }

    public function test_user_can_view_any_ride(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_ride(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->taxiRide));
    }

    public function test_user_can_create_ride(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_own_ride(): void
    {
        $this->taxiRide->driver_id = $this->user->id;
        $this->assertTrue($this->policy->update($this->user, $this->taxiRide));
    }

    public function test_user_cannot_update_others_ride(): void
    {
        $this->taxiRide->driver_id = User::factory()->create()->id;
        $this->assertFalse($this->policy->update($this->user, $this->taxiRide));
    }

    public function test_user_can_delete_own_ride(): void
    {
        $this->taxiRide->driver_id = $this->user->id;
        $this->assertTrue($this->policy->delete($this->user, $this->taxiRide));
    }
}
