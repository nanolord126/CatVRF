<?php

declare(strict_types=1);

namespace Tests\Contract\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NotificationApiContractTest
 * 
 * Контрактные тесты - валидация структуры ответов по OpenAPI
 */
final class NotificationApiContractTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_conforms_to_list_notifications_contract(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'tenant_id',
                    'type',
                    'title',
                    'body',
                    'channels',
                    'status',
                    'read_at',
                    'sent_at',
                    'correlation_id',
                    'created_at',
                    'updated_at',
                ]
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_view_notification_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'tenant_id',
                'type',
                'title',
                'body',
                'channels',
                'status',
                'data',
                'metadata',
                'read_at',
                'sent_at',
                'correlation_id',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_create_notification_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/notifications', [
                'type' => 'test.notification',
                'title' => 'Test',
                'body' => 'Test body',
                'channels' => ['email', 'sms'],
            ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'title',
                'body',
                'channels',
                'status',
                'correlation_id',
                'created_at',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_update_notification_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$notification->id}", [
                'status' => 'read',
            ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'read_at',
                'updated_at',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_delete_notification_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertNoContent();
    }

    /** @test */
    public function it_conforms_to_preference_list_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'notification_type',
                    'enabled',
                    'email_enabled',
                    'sms_enabled',
                    'push_enabled',
                    'database_enabled',
                    'frequency',
                    'quiet_hours_enabled',
                    'quiet_hours_start',
                    'quiet_hours_end',
                    'max_per_day',
                    'created_at',
                    'updated_at',
                ]
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_preference_view_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences');

        if ($response['meta']['total'] > 0) {
            $preferenceId = $response['data'][0]['id'];

            $viewResponse = $this->actingAs($user)
                ->getJson("/api/v1/notification-preferences/{$preferenceId}");

            $viewResponse->assertJsonStructure([
                'data' => [
                    'id',
                    'notification_type',
                    'enabled',
                    'email_enabled',
                    'sms_enabled',
                    'push_enabled',
                    'database_enabled',
                    'frequency',
                    'quiet_hours_enabled',
                    'quiet_hours_start',
                    'quiet_hours_end',
                    'max_per_day',
                ]
            ]);
        }
    }

    /** @test */
    public function it_conforms_to_bulk_update_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/notification-preferences/bulk-update', [
                'notification_type' => 'payment.*',
                'enabled' => true,
            ]);

        $response->assertJsonStructure([
            'data' => [
                'updated' => [],
                'count',
            ]
        ]);
    }

    /** @test */
    public function it_returns_proper_error_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications/invalid-id');

        $response->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'trace',
            ]
        ]);
    }

    /** @test */
    public function it_conforms_to_validation_error_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/notifications', [
                'type' => '',
                'title' => '',
            ]);

        $response->assertJsonStructure([
            'errors' => [
                '*' => [
                    'field',
                    'message',
                ]
            ]
        ]);
    }

    /** @test */
    public function it_includes_correlation_id_in_all_responses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $this->assertNotNull($response['meta']['correlation_id'] ?? null);
    }

    /** @test */
    public function it_includes_rate_limit_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $this->assertNotNull($response->header('X-RateLimit-Limit'));
        $this->assertNotNull($response->header('X-RateLimit-Remaining'));
    }

    /** @test */
    public function it_returns_proper_pagination_metadata(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(25)->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?per_page=10');

        $response->assertJsonStructure([
            'meta' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'from',
                'to',
            ]
        ]);

        $this->assertEquals(25, $response['meta']['total']);
        $this->assertEquals(10, $response['meta']['per_page']);
    }

    /** @test */
    public function it_conforms_to_timestamps_format(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        // ISO 8601 format
        $createdAt = $response['data']['created_at'];
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/',
            $createdAt
        );
    }

    /** @test */
    public function it_conforms_to_boolean_fields_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences');

        if ($response['meta']['total'] > 0) {
            $preference = $response['data'][0];

            $this->assertIsBool($preference['enabled']);
            $this->assertIsBool($preference['email_enabled']);
            $this->assertIsBool($preference['sms_enabled']);
            $this->assertIsBool($preference['quiet_hours_enabled']);
        }
    }

    /** @test */
    public function it_conforms_to_array_fields_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $this->assertIsArray($response['data']['channels']);
        $this->assertIsArray($response['data']['data'] ?? []);
    }

    /** @test */
    public function it_conforms_to_enum_fields_contract(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences');

        if ($response['meta']['total'] > 0) {
            $preference = $response['data'][0];

            // Frequency should be one of: immediate, daily_digest, weekly_digest
            $validFrequencies = ['immediate', 'daily_digest', 'weekly_digest'];
            $this->assertContains($preference['frequency'], $validFrequencies);
        }
    }

    /** @test */
    public function it_conforms_to_nullable_fields_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create([
            'read_at' => null,
            'sent_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $this->assertNull($response['data']['read_at']);
        $this->assertNull($response['data']['sent_at']);
    }

    /** @test */
    public function it_conforms_to_uuid_fields_contract(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        // UUID format validation
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($uuidPattern, $response['data']['id']);
    }

    /** @test */
    public function it_conforms_to_http_status_codes(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        // 200 OK
        $this->actingAs($user)
            ->getJson('/api/v1/notifications')
            ->assertOk();

        // 201 Created
        $this->actingAs($user)
            ->postJson('/api/v1/notifications', [
                'type' => 'test',
                'title' => 'Test',
                'body' => 'Test',
                'channels' => ['email'],
            ])
            ->assertCreated();

        // 204 No Content
        $this->actingAs($user)
            ->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertNoContent();

        // 404 Not Found
        $this->actingAs($user)
            ->getJson('/api/v1/notifications/invalid')
            ->assertNotFound();

        // 401 Unauthorized
        $this->getJson('/api/v1/notifications')
            ->assertUnauthorized();
    }
}
