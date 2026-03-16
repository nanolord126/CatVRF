<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\RestaurantOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RestaurantOrderResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_process_orders():void{$this->post('/admin/restaurant-orders',['order_number'=>'ORD-2026-001','total_amount'=>'49.99']);$this->assertDatabaseHas('restaurant_orders',['order_number'=>'ORD-2026-001']);}}