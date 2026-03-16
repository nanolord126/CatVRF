<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MultiTenantTest extends TestCase
{use RefreshDatabase;public function test_resources_properly_scoped_by_tenant():void{$user1=User::factory()->create(['tenant_id'=>'tenant-1']);$user2=User::factory()->create(['tenant_id'=>'tenant-2']);Restaurant::factory(3)->create(['tenant_id'=>$user1->tenant_id]);Restaurant::factory(2)->create(['tenant_id'=>$user2->tenant_id]);$this->actingAs($user1);$response=$this->get('/admin/restaurants');$this->assertCount(3,Restaurant::where('tenant_id',$user1->tenant_id)->get());}}