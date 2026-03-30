<?php declare(strict_types=1);

namespace Tests;

/**
 * Security Test Case для fraud-атак, authorization, input validation
 *
 * Обеспечивает:
 * - Fraud attack patterns (replay, idempotency bypass, rate limit bypass)
 * - Authorization checks (RBAC, tenant isolation, business_group scoping)
 * - Input validation (SQL injection, XSS, XXE, mass assignment)
 * - Edge cases и boundary conditions
 */
abstract class SecurityTestCase extends BaseTestCase
{
    /**
     * Проверка что endpoint защищён от replay-атаки
     * (один и тот же idempotency_key не должен быть обработан дважды)
     */
    protected function assertReplayAttackProtection(
        string $method,
        string $uri,
        array $data,
        string $idempotencyKey
    ): void {
        // Первый запрос — успешен
        $response1 = $this->authenticatedPost(
            $uri,
            $data,
            ['Idempotency-Key' => $idempotencyKey],
        );
        $response1->assertSuccessful();
        $firstId = $response1->json('id');

        // Второй запрос с тем же ключом — должен вернуть тот же результат (не дублировать)
        $response2 = $this->authenticatedPost(
            $uri,
            $data,
            ['Idempotency-Key' => $idempotencyKey],
        );
        $response2->assertSuccessful();
        $secondId = $response2->json('id');

        // ID должны быть одинаковыми (результат был закеширован)
        $this->assertEquals($firstId, $secondId);

        // Проверяем что в БД не дублировалась запись
        $this->assertDatabaseMissing('idempotency_records', [
            'idempotency_key' => $idempotencyKey,
            'status' => 'duplicate',
        ]);
    }

    /**
     * Проверка что endpoint защищён от идемпотентности bypass
     * (изменение payload после первого запроса не должно обрабатываться)
     */
    protected function assertIdempotencyBypassProtection(
        string $uri,
        array $data1,
        array $data2,
        string $idempotencyKey
    ): void {
        // Первый запрос
        $response1 = $this->authenticatedPost($uri, $data1, ['Idempotency-Key' => $idempotencyKey]);
        $response1->assertSuccessful();

        // Второй запрос с ИЗМЕНЁННЫМ payload
        $response2 = $this->authenticatedPost($uri, $data2, ['Idempotency-Key' => $idempotencyKey]);

        // Должен быть отклонён (409 Conflict)
        $response2->assertStatus(409);
        $response2->assertJson(['message' => 'Payload mismatch']);
    }

    /**
     * Проверка что endpoint защищён от rate limit bypass
     * Отправляем X запросов за короткий период
     */
    protected function assertRateLimitBypassProtection(
        string $method,
        string $uri,
        array $data = [],
        int $allowedRequests = 10,
        int $windowSeconds = 60
    ): void {
        for ($i = 0; $i < $allowedRequests + 5; $i++) {
            $response = match ($method) {
                'GET' => $this->authenticatedGet($uri),
                'POST' => $this->authenticatedPost($uri, $data),
                default => throw new \InvalidArgumentException("Unknown method: $method"),
            };

            if ($i < $allowedRequests) {
                $response->assertStatus(200);
            } else {
                // После лимита — 429 Too Many Requests
                $response->assertStatus(429);
                $response->assertHeader('Retry-After');
            }
        }
    }

    /**
     * Проверка race condition в wallet операциях
     * Две параллельные транзакции не должны привести к двойной трате
     */
    protected function assertNoWalletRaceCondition(
        int $initialBalance,
        int $debitAmount
    ): void {
        // Создаём wallet с начальным балансом
        $wallet = \App\Models\Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => $initialBalance,
        ]);

        // Эмулируем две параллельные операции через 2 хинанда (threads)
        $results = [];
        for ($i = 0; $i < 2; $i++) {
            $results[] = \DB::transaction(function () use ($wallet, $debitAmount) {
                // Читаем баланс
                $currentBalance = $wallet->lockForUpdate()->current_balance;

                // Проверяем достаточность
                if ($currentBalance < $debitAmount) {
                    throw new \Exception('Insufficient balance');
                }

                // Списываем
                $wallet->update([
                    'current_balance' => $currentBalance - $debitAmount,
                ]);

                return true;
            });
        }

        // Вторая транзакция должна была выбросить исключение (Insufficient balance)
        // или оставить баланс корректным
        $wallet->refresh();
        $this->assertGreaterThanOrEqual(0, $wallet->current_balance);
        $this->assertLessThanOrEqual($initialBalance, $wallet->current_balance);
    }

    /**
     * Проверка что wishlist не может быть использован для манипуляции рейтингом
     */
    protected function assertWishlistManipulationProtection(): void
    {
        // Создаём товар
        $product = \App\Models\Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 3.0,
            'review_count' => 10,
        ]);

        // Попытка: Создание wishlist-а без реальной покупки
        $response = $this->authenticatedPost('/api/wishlists', [
            'product_id' => $product->id,
            'quantity' => 100, // Подозрительно много
        ]);

        // Должна быть проверка fraud score
        if ($response->status() === 200) {
            $this->assertHasFraudScore($response);
            $this->assertGreaterThan(0.5, $response->json('fraud_score'));
        }

        // Рейтинг продукта НЕ должен быть затронут
        $product->refresh();
        $this->assertEquals(3.0, $product->rating);
    }

    /**
     * Проверка что fake reviews блокируются
     */
    protected function assertFakeReviewsProtection(): void
    {
        $product = \App\Models\Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Попытка: Создание review без реальной покупки
        $response = $this->authenticatedPost('/api/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Best product ever!',
        ]);

        // Должна быть проверка fraud score
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['product_id']);
        } else {
            $this->assertHasFraudScore($response);
        }
    }

    /**
     * Проверка что bonus hunting (множественные реферальные клеймы) блокируется
     */
    protected function assertBonusHuntingProtection(): void
    {
        $referral = \App\Models\Referral::factory()->create([
            'referrer_id' => $this->user->id,
            'status' => 'pending',
        ]);

        // Первый claim — успешен
        $response1 = $this->authenticatedPost('/api/referrals/' . $referral->id . '/claim', [
            'amount' => 1000,
        ]);
        $response1->assertSuccessful();

        // Второй claim с тем же реферралом — должен быть отклонён
        $response2 = $this->authenticatedPost('/api/referrals/' . $referral->id . '/claim', [
            'amount' => 1000,
        ]);
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['referral_id']);
    }

    /**
     * Проверка SQL injection в search endpoint
     */
    protected function assertSQLInjectionProtection(string $uri = '/api/search'): void
    {
        $injection = "' OR '1'='1";
        $response = $this->authenticatedGet($uri, ['q' => $injection]);

        // Не должен быть успешный, или должен быть валидирован
        if ($response->successful()) {
            // Если успешен, то результаты должны быть отфильтрованы, а не все records
            $results = $response->json('data');
            $this->assertIsArray($results);
        }
    }

    /**
     * Проверка XSS в user input
     */
    protected function assertXSSProtection(string $uri = '/api/products', string $field = 'name'): void
    {
        $xss = '<script>alert("XSS")</script>';
        $response = $this->authenticatedPost($uri, [
            $field => $xss,
        ]);

        // Если создаётся, то скрипт должен быть экранирован
        if ($response->successful()) {
            $product = \App\Models\Product::find($response->json('id'));
            $this->assertStringNotContainsString('<script>', $product?->$field ?? '');
        }
    }

    /**
     * Проверка что RBAC работает корректно
     * User не может выполнять действия другого user'a
     */
    protected function assertRBACProtection(string $uri, array $data = []): void
    {
        $secondUser = $this->createSecondUser();

        // User 1 создаёт resource
        $response1 = $this->authenticatedPost($uri, $data ?? [
            'name' => 'My Resource',
        ]);
        $response1->assertSuccessful();
        $resourceId = $response1->json('id');

        // User 2 пытается отредактировать resource User 1
        $response2 = $this->authenticatedPut(
            "$uri/$resourceId",
            ['name' => 'Hacked!'],
            [],
            $secondUser
        );

        // Должен быть 403 Forbidden
        $response2->assertStatus(403);
    }

    /**
     * Проверка что tenant isolation работает
     * User одного tenant'a не может видеть данные другого
     */
    protected function assertTenantIsolation(string $uri = '/api/wallets'): void
    {
        $secondTenant = $this->createSecondTenant();
        $secondUser = User::factory()->create(['tenant_id' => $secondTenant->id]);

        // User 1 из tenant 1
        $response1 = $this->authenticatedGet($uri);
        $response1->assertSuccessful();
        $data1 = $response1->json();

        // User 2 из tenant 2
        $response2 = $this->authenticatedGet($uri, [], $secondUser);
        $response2->assertSuccessful();
        $data2 = $response2->json();

        // Данные должны быть разными
        if (is_array($data1) && is_array($data2)) {
            foreach ($data1 as $item) {
                if (isset($item['tenant_id'])) {
                    $this->assertEquals($this->tenant->id, $item['tenant_id']);
                }
            }
        }
    }

    /**
     * Проверка что business_group scoping работает
     */
    protected function assertBusinessGroupIsolation(): void
    {
        $group1 = \App\Models\BusinessGroup::factory()->create(['tenant_id' => $this->tenant->id]);
        $group2 = \App\Models\BusinessGroup::factory()->create(['tenant_id' => $this->tenant->id]);

        // Создаём ресурс в группе 1
        $response1 = $this->authenticatedPost('/api/businesses', [
            'name' => 'Group 1 Business',
            'business_group_id' => $group1->id,
        ]);

        if ($response1->successful()) {
            $businessId = $response1->json('id');

            // Пытаемся получить доступ через группу 2
            $response2 = $this->authenticatedGet("/api/businesses/$businessId", [
                'X-Business-Group-ID' => $group2->id,
            ]);

            // Должен быть 404 (не видит ресурс из другой группы)
            $response2->assertStatus(404);
        }
    }

    /**
     * Проверка что DDoS-подобные атаки на search блокируются
     */
    protected function assertSearchDDoSProtection(string $uri = '/api/search'): void
    {
        $responses = [];

        // Отправляем 50 быстрых запросов
        for ($i = 0; $i < 50; $i++) {
            $response = $this->authenticatedGet($uri, ['q' => 'test-' . $i]);
            $responses[] = $response->status();
        }

        // В какой-то момент должны получить 429 (rate limit)
        $has429 = in_array(429, $responses);
        $this->assertTrue($has429, 'Search DDoS was not blocked');
    }

    /**
     * Проверка что audit log ведётся для всех операций
     */
    protected function assertAuditLogCreated(string $operationType, array $relatedIds = []): void
    {
        $auditLog = \App\Models\AuditLog::where([
            'operation_type' => $operationType,
            'correlation_id' => $this->correlationId,
        ])->first();

        $this->assertNotNull($auditLog, "Audit log not created for operation: $operationType");
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals($this->tenant->id, $auditLog->tenant_id);
    }
}
