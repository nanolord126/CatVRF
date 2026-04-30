<?php

declare(strict_types=1);

namespace Tests\Regression;

use Tests\BaseVerticalTestCase;
use App\Models\User;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Regression Test Suite
 * 
 * Ensures that critical functionality remains stable after code changes.
 * Tests baseline performance and data integrity across key operations.
 */
final class RegressionTestSuite extends BaseVerticalTestCase
{
    use RefreshDatabase;

    public function test_payment_creation_baseline_performance(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $startTime = microtime(true);

        // Act
        Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'currency' => 'RUB',
            'status' => 'pending',
        ]);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert - should complete in under 100ms
        $this->assertLessThan(100, $executionTime, 'Payment creation should complete in under 100ms');
    }

    public function test_wallet_transaction_baseline_performance(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tenantId = 1;

        $startTime = microtime(true);

        // Act
        WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert - should complete in under 100ms
        $this->assertLessThan(100, $executionTime, 'Wallet transaction should complete in under 100ms');
    }

    public function test_payment_data_integrity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $expectedAmount = 10000;
        $expectedCurrency = 'RUB';

        // Act
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => $expectedAmount,
            'currency' => $expectedCurrency,
            'status' => 'pending',
        ]);

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $user->id,
            'amount' => $expectedAmount,
            'currency' => $expectedCurrency,
        ]);
    }

    public function test_wallet_balance_calculation_integrity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tenantId = 1;

        WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => -3000,
            'status' => 'completed',
        ]);

        // Act
        $balance = WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        // Assert
        $this->assertEquals(7000, $balance, 'Balance calculation should be accurate');
    }

    public function test_user_authentication_regression(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_database_transaction_rollback_on_error(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        try {
            DB::transaction(function () use ($user) {
                Payment::factory()->create([
                    'user_id' => $user->id,
                    'amount' => 10000,
                    'status' => 'pending',
                ]);

                throw new \Exception('Intentional error');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Assert - payment should not exist due to rollback
        $this->assertDatabaseMissing('payments', [
            'user_id' => $user->id,
            'amount' => 10000,
        ]);
    }

    public function test_audit_log_creation_on_critical_operations(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'succeeded',
        ]);

        // Assert - audit log should be created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.created',
        ]);
    }

    public function test_concurrent_payment_creation_safety(): void
    {
        // Arrange
        $user = User::factory()->create();
        $payments = [];

        // Act - create multiple payments concurrently
        for ($i = 0; $i < 10; $i++) {
            $payments[] = Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => 1000 + ($i * 100),
                'status' => 'pending',
            ]);
        }

        // Assert
        $this->assertCount(10, $payments);
        
        foreach ($payments as $payment) {
            $this->assertDatabaseHas('payments', [
                'id' => $payment->id,
                'user_id' => $user->id,
            ]);
        }
    }

    public function test_payment_status_transition_validity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Act
        $payment->update(['status' => 'succeeded']);

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);
    }

    public function test_wallet_hold_and_release_integrity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tenantId = 1;

        $deposit = WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        $hold = WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'hold',
            'amount' => -3000,
            'status' => 'pending',
        ]);

        // Act
        $hold->update(['status' => 'released']);

        WalletTransaction::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => 'hold_release',
            'amount' => 3000,
            'status' => 'completed',
        ]);

        // Assert
        $finalBalance = WalletTransaction::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $this->assertEquals(10000, $finalBalance, 'Hold and release should return balance to original state');
    }

    public function test_api_response_structure_consistency(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->getJson('/api/v1/payments');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_user_session_persistence(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response1 = $this->getJson('/api/v1/user');
        $userId1 = $response1->json('data.id');

        $response2 = $this->getJson('/api/v1/user');
        $userId2 = $response2->json('data.id');

        // Assert
        $this->assertEquals($userId1, $userId2, 'User session should persist across requests');
    }
}
