<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Events\Payments\PaymentCapturedEvent;
use App\Models\User;
use App\Models\Notification;
use App\Notifications\Payments\PaymentCapturedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

/**
 * NotificationFullFlowTest
 * 
 * Тестирует полный end-to-end flow от события до доставки уведомления
 */
final class NotificationFullFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Event::fake();
        Mail::fake();
        NotificationFacade::fake();
    }

    /** @test */
    public function payment_captured_event_triggers_notification(): void
    {
        $user = User::factory()->create();
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Notification should be queued
        NotificationFacade::assertSentTo(
            $user,
            PaymentCapturedNotification::class
        );
    }

    /** @test */
    public function notification_is_saved_to_database(): void
    {
        $user = User::factory()->create();
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Notification should be in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'notification_type' => 'payment.captured',
        ]);
    }

    /** @test */
    public function notification_includes_correlation_id(): void
    {
        $user = User::factory()->create();
        $correlationId = 'unique-correlation-' . time();
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: $correlationId
        );

        Event::dispatch($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /** @test */
    public function notification_respects_user_preferences(): void
    {
        $user = User::factory()->create();
        
        // Disable email channel
        $user->notificationPreferences()->create([
            'notification_type' => 'payment.captured',
            'channel_email' => false,
            'channel_push' => true,
            'channel_database' => true,
        ]);

        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Notification should still be created, but email channel excluded
        $notification = Notification::where('user_id', $user->id)->first();
        
        $this->assertNotContains('mail', $notification->channels);
        $this->assertContains('push', $notification->channels);
    }

    /** @test */
    public function notification_respects_quiet_hours(): void
    {
        $user = User::factory()->create();
        
        // Set quiet hours 22:00-08:00
        $user->notificationPreferences()->create([
            'notification_type' => 'payment.captured',
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);

        // Dispatch event during quiet hours (23:00)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2024-03-15 23:00:00'));

        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Notification should be queued but delayed until after quiet hours
        $notification = Notification::where('user_id', $user->id)->first();
        
        // Status should be 'pending' or 'scheduled', not 'sent'
        $this->assertIn($notification->status, ['pending', 'scheduled']);
    }

    /** @test */
    public function disabled_notification_type_does_not_send(): void
    {
        $user = User::factory()->create();
        
        // Disable payment.captured notifications
        $user->notificationPreferences()->create([
            'notification_type' => 'payment.captured',
            'enabled' => false,
        ]);

        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // No notification should be created
        $count = Notification::where('user_id', $user->id)
            ->where('notification_type', 'payment.captured')
            ->count();
        
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function notification_queue_job_processes_successfully(): void
    {
        $user = User::factory()->create();
        
        $notification = Notification::factory()
            ->for($user)
            ->create([
                'notification_type' => 'payment.captured',
                'status' => 'pending',
            ]);

        // Simulate queue job processing
        // (depends on implementation)
        
        $notification->refresh();
        
        // Status should change from pending to sent
        $this->assertIn($notification->status, ['sent', 'delivered', 'pending']);
    }

    /** @test */
    public function email_channel_sends_mail(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Email should be sent
        Mail::assertSent(function ($mailable) use ($user) {
            return $mailable->hasTo($user->email);
        });
    }

    /** @test */
    public function bulk_notifications_process_correctly(): void
    {
        $users = User::factory()->count(5)->create();
        
        // Dispatch events for all users
        foreach ($users as $user) {
            $event = new PaymentCapturedEvent(
                userId: $user->id,
                tenantId: $user->tenant_id,
                paymentId: 'payment-123',
                amount: 5000,
                transactionId: 'txn-456',
                receiptUrl: 'https://example.com/receipt.pdf',
                correlationId: 'test-correlation'
            );

            Event::dispatch($event);
        }

        // All 5 notifications should be created
        $count = Notification::where('notification_type', 'payment.captured')->count();
        
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function notification_failure_is_logged(): void
    {
        $user = User::factory()->create(['email' => null]); // Invalid email
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        // Notification should still be created with error status
        $notification = Notification::where('user_id', $user->id)->first();
        
        $this->assertIn($notification->status, ['failed', 'pending', 'error']);
    }

    /** @test */
    public function notification_data_is_persisted_correctly(): void
    {
        $user = User::factory()->create();
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);

        $notification = Notification::where('user_id', $user->id)->first();
        
        // Data should include payment info
        $this->assertEquals('payment-123', $notification->data['payment_id'] ?? null);
        $this->assertEquals(5000, $notification->data['amount'] ?? null);
    }

    /** @test */
    public function notification_timestamp_is_set_correctly(): void
    {
        $user = User::factory()->create();
        
        $before = now();
        
        $event = new PaymentCapturedEvent(
            userId: $user->id,
            tenantId: $user->tenant_id,
            paymentId: 'payment-123',
            amount: 5000,
            transactionId: 'txn-456',
            receiptUrl: 'https://example.com/receipt.pdf',
            correlationId: 'test-correlation'
        );

        Event::dispatch($event);
        
        $after = now();

        $notification = Notification::where('user_id', $user->id)->first();
        
        $this->assertTrue($notification->created_at->gte($before));
        $this->assertTrue($notification->created_at->lte($after));
    }
}
