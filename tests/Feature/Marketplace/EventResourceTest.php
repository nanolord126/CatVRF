<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EventResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_create_events():void{$this->post('/admin/events',['name'=>'Conference 2026','start_time'=>now(),'end_time'=>now()->addDays(2),'price'=>'99.99']);$this->assertDatabaseHas('events',['name'=>'Conference 2026']);}}