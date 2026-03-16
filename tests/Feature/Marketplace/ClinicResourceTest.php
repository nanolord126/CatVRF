<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ClinicResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_unauthorized_user_cannot_access_other_clinics():void{$otherUser=User::factory()->create();$clinic=Clinic::factory()->create(['tenant_id'=>$otherUser->tenant_id]);$response=$this->get("/admin/clinics/{$clinic->id}");$response->assertForbidden();}public function test_authorized_user_can_manage_clinics():void{$clinic=Clinic::factory()->create(['tenant_id'=>$this->user->tenant_id]);$this->patch("/admin/clinics/{$clinic->id}",['name'=>'Updated Clinic']);$clinic->refresh();$this->assertEquals('Updated Clinic',$clinic->name);}}