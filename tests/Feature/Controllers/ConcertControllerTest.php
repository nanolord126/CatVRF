<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Tenants\Concert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * ConcertControllerTest - Интеграционный тест для Concert API
 */
final class ConcertControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('manager');
        $this->actingAs($this->user);
    }

    /**
     * Тест: пользователь может получить список концертов
     */
    public function test_user_can_list_concerts(): void
    {
        Concert::factory(3)->create([
            'tenant_id' => tenant('id'),
        ]);

        $response = $this->getJson('/api/concerts');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    /**
     * Тест: пользователь может создать концерт
     */
    public function test_user_can_create_concert(): void
    {
        $data = [
            'name' => 'Test Concert',
            'description' => 'A test concert event',
            'date' => now()->addDays(30)->toDateString(),
            'time' => '19:00',
            'venue' => 'Test Hall',
            'address' => 'Test Address',
            'price' => 50.00,
            'capacity' => 500,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/concerts', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'Test Concert');
        $this->assertDatabaseHas('concerts', ['name' => 'Test Concert']);
    }

    /**
     * Тест: пользователь может просмотреть концерт
     */
    public function test_user_can_view_concert(): void
    {
        $concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);

        $response = $this->getJson("/api/concerts/{$concert->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('name', $concert->name);
    }

    /**
     * Тест: пользователь может обновить концерт
     */
    public function test_user_can_update_concert(): void
    {
        $concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);

        $data = ['name' => 'Updated Concert Name'];

        $response = $this->putJson("/api/concerts/{$concert->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('concerts', [
            'id' => $concert->id,
            'name' => 'Updated Concert Name',
        ]);
    }

    /**
     * Тест: пользователь может удалить концерт
     */
    public function test_user_can_delete_concert(): void
    {
        $concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);

        $response = $this->deleteJson("/api/concerts/{$concert->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($concert);
    }

    /**
     * Тест: неавторизованный пользователь не может получить доступ
     */
    public function test_unauthorized_user_cannot_access(): void
    {
        $this->actingAs(null);

        $response = $this->getJson('/api/concerts');

        $response->assertStatus(401);
    }

    /**
     * Тест: пользователь не может получить доступ к концерту другого тенанта
     */
    public function test_user_cannot_access_concert_from_different_tenant(): void
    {
        $otherConcert = Concert::factory()->create([
            'tenant_id' => 'other-tenant',
        ]);

        $response = $this->getJson("/api/concerts/{$otherConcert->id}");

        $response->assertStatus(403);
    }

    /**
     * Тест: менеджер может удалить концерт
     */
    public function test_manager_can_delete_concert(): void
    {
        $concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);

        $response = $this->deleteJson("/api/concerts/{$concert->id}");

        $response->assertStatus(200);
    }

    /**
     * Тест: зритель не может удалить концерт
     */
    public function test_viewer_cannot_delete_concert(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        $this->actingAs($viewer);

        $concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);

        $response = $this->deleteJson("/api/concerts/{$concert->id}");

        $response->assertStatus(403);
    }
}
