<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\TaxiTrip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TaxiTripResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_track_taxi_trips():void{$this->post('/admin/taxi-trips',['start_location'=>'123 Main St','end_location'=>'456 Oak Ave','fare'=>'25.50']);$this->assertDatabaseHas('taxi_trips',['start_location'=>'123 Main St']);}}