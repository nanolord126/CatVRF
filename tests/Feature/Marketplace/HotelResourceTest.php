<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HotelResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_list_hotels():void{Hotel::factory(4)->create(['tenant_id'=>$this->user->tenant_id]);$response=$this->get('/admin/hotels');$response->assertOk();}public function test_can_show_hotel():void{$hotel=Hotel::factory()->create(['tenant_id'=>$this->user->tenant_id]);$response=$this->get("/admin/hotels/{$hotel->id}");$response->assertOk();}public function test_can_delete_hotel():void{$hotel=Hotel::factory()->create(['tenant_id'=>$this->user->tenant_id]);$this->delete("/admin/hotels/{$hotel->id}");$this->assertSoftDeleted('hotels',['id'=>$hotel->id]);}}