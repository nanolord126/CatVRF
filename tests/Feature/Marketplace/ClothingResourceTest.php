<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ClothingResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_catalog_clothing():void{$this->post('/admin/clothings',['name'=>'T-Shirt','size'=>'M','price'=>'29.99']);$this->assertDatabaseHas('clothings',['name'=>'T-Shirt']);}}