<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\GardenProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GardenProductResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_garden_products():void{$this->post('/admin/garden-products',['name'=>'Fertilizer','quantity'=>'100','price'=>'29.99']);$this->assertDatabaseHas('garden_products',['name'=>'Fertilizer']);}}