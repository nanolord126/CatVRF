<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Electronics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ElectronicsResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_catalog_electronics():void{$this->post('/admin/electronics',['name'=>'Laptop','model'=>'XPS 13','price'=>'1299.99']);$this->assertDatabaseHas('electronics',['model'=>'XPS 13']);}}