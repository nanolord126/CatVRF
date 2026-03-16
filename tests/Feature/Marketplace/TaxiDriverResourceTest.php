<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\TaxiDriver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TaxiDriverResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_register_drivers():void{$this->post('/admin/taxi-drivers',['name'=>'John Driver','phone'=>'+1234567890','rating'=>'4.8']);$this->assertDatabaseHas('taxi_drivers',['name'=>'John Driver']);}}