<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\Perfumery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PerfumeryResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_catalog_perfumes():void{$this->post('/admin/perfumeries',['name'=>'Chanel No. 5','brand'=>'Chanel','price'=>'150.00']);$this->assertDatabaseHas('perfumeries',['brand'=>'Chanel']);}}