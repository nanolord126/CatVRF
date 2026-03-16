<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CrudOperationsTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_full_crud_cycle():void{$restaurant=Restaurant::factory()->create(['tenant_id'=>$this->user->tenant_id]);$this->assertDatabaseHas('restaurants',['id'=>$restaurant->id]);$restaurant->update(['name'=>'Updated Name']);$this->assertDatabaseHas('restaurants',['name'=>'Updated Name']);$restaurant->delete();$this->assertSoftDeleted('restaurants',['id'=>$restaurant->id]);}}