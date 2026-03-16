<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\VetClinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VetClinicResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_vet_clinics():void{$this->post('/admin/vet-clinics',['name'=>'Pet Care Clinic','address'=>'999 Pet Street']);$this->assertDatabaseHas('vet_clinics',['name'=>'Pet Care Clinic']);}}