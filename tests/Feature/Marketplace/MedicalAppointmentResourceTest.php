<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\MedicalAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MedicalAppointmentResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_schedule_appointments():void{$this->post('/admin/medical-appointments',['patient_name'=>'John Doe','appointment_time'=>now()->addDay()]);$this->assertDatabaseHas('medical_appointments',['patient_name'=>'John Doe']);}}