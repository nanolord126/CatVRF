<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GymResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_gyms():void{$this->post('/admin/gyms',['name'=>'Premium Fitness','address'=>'789 Gym Lane']);$this->assertDatabaseHas('gyms',['name'=>'Premium Fitness']);}}