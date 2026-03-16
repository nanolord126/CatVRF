<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\FlowersOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FlowersOrderResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_process_flower_orders():void{$this->post('/admin/flowers-orders',['order_number'=>'ORD-001','total_amount'=>'99.99']);$this->assertDatabaseHas('flowers_orders',['order_number'=>'ORD-001']);}}