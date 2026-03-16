<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FlowerResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_view_flowers():void{Flower::factory(5)->create(['tenant_id'=>$this->user->tenant_id]);$response=$this->get('/admin/flowers');$response->assertOk();}public function test_can_create_flower():void{$this->post('/admin/flowers',['name'=>'Rose','price'=>'29.99']);$this->assertDatabaseHas('flowers',['name'=>'Rose']);}public function test_can_update_flower():void{$flower=Flower::factory()->create(['tenant_id'=>$this->user->tenant_id]);$this->patch("/admin/flowers/{$flower->id}",['name'=>'Updated Rose']);$flower->refresh();$this->assertEquals('Updated Rose',$flower->name);}}