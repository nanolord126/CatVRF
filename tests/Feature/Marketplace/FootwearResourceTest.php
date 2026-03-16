<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Footwear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FootwearResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_shoes():void{$this->post('/admin/footwears',['name'=>'Nike Air','size'=>'42','price'=>'120.00']);$this->assertDatabaseHas('footwears',['name'=>'Nike Air']);}}