<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Tenants\Concert;
use App\Policies\Marketplace\ConcertPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ConcertPolicyTest - Тестирование авторизации для Concert модели
 */
final class ConcertPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $viewer;
    private Concert $concert;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовых пользователей
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->viewer = User::factory()->create();
        $this->viewer->assignRole('viewer');

        // Создаем тестовый концерт
        $this->concert = Concert::factory()->create([
            'tenant_id' => tenant('id'),
        ]);
    }

    /**
     * Администратор может просмотреть любой концерт
     */
    public function test_admin_can_view_any_concert(): void
    {
        $this->assertTrue(
            $this->admin->can('view', $this->concert)
        );
    }

    /**
     * Менеджер может просмотреть концерт его тенанта
     */
    public function test_manager_can_view_concert_in_their_tenant(): void
    {
        $this->assertTrue(
            $this->manager->can('view', $this->concert)
        );
    }

    /**
     * Зритель может просмотреть, но не редактировать концерт
     */
    public function test_viewer_cannot_update_concert(): void
    {
        $this->assertFalse(
            $this->viewer->can('update', $this->concert)
        );
    }

    /**
     * Менеджер может создать новый концерт
     */
    public function test_manager_can_create_concert(): void
    {
        $this->assertTrue(
            $this->manager->can('create', Concert::class)
        );
    }

    /**
     * Зритель не может удалить концерт
     */
    public function test_viewer_cannot_delete_concert(): void
    {
        $this->assertFalse(
            $this->viewer->can('delete', $this->concert)
        );
    }

    /**
     * Неактивный пользователь не может выполнять никакие действия
     */
    public function test_inactive_user_cannot_perform_actions(): void
    {
        $inactive = User::factory()->create(['active' => false]);
        $inactive->assignRole('manager');

        $this->assertFalse(
            $inactive->can('view', $this->concert)
        );
    }

    /**
     * Пользователь не может редактировать концерт из другого тенанта
     */
    public function test_user_cannot_update_concert_from_different_tenant(): void
    {
        $otherConcert = Concert::factory()->create([
            'tenant_id' => 'other-tenant-id',
        ]);

        $this->assertFalse(
            $this->manager->can('update', $otherConcert)
        );
    }
}
