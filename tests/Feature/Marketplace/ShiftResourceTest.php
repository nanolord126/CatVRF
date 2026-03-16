<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ShiftResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_schedule_shifts():void{$this->post('/admin/shifts',['employee_id'=>1,'start_time'=>now(),'end_time'=>now()->addHours(8)]);$this->assertDatabaseHas('shifts',['employee_id'=>1]);}}