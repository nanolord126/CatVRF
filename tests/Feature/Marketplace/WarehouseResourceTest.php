<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class WarehouseResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_warehouses():void{$this->post('/admin/warehouses',['name'=>'Main Warehouse','address'=>'123 Warehouse St','capacity'=>1000]);$this->assertDatabaseHas('warehouses',['name'=>'Main Warehouse']);}}