<?php
namespace Tests\Unit\Policies;
use App\Models\Marketplace\Restaurant;
use App\Models\User;
use App\Policies\Marketplace\RestaurantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_other_tenant_resource(): void
    {
        $policy = new RestaurantPolicy();
        $user1 = User::factory()->create(['tenant_id' => 'tenant-1']);
        $user2 = User::factory()->create(['tenant_id' => 'tenant-2']);
        $restaurant = Restaurant::factory()->create(['tenant_id' => $user1->tenant_id]);
        
        $result = $policy->view($user2, $restaurant);
        
        $this->assertFalse($result->allowed());
    }

    public function test_user_can_view_own_tenant_resource(): void
    {
        $policy = new RestaurantPolicy();
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $result = $policy->view($user, $restaurant);
        
        $this->assertTrue($result->allowed());
    }
}