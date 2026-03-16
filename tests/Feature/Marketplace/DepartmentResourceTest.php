<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DepartmentResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_manage_departments():void{$this->post('/admin/departments',['name'=>'IT Department','manager_id'=>$this->user->id]);$this->assertDatabaseHas('departments',['name'=>'IT Department']);}}