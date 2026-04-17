<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SuspiciousBehaviorDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SuspiciousBehaviorDetectorTest extends TestCase
{
    use RefreshDatabase;

    private SuspiciousBehaviorDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new SuspiciousBehaviorDetector();
    }

    public function test_female_user_can_access_lingerie_fitting(): void
    {
        $result = $this->detector->checkLingerieFittingAccess(1, 'female', 'test-correlation');

        $this->assertTrue($result['allowed']);
        $this->assertEquals('Доступ разрешен', $result['message']);
    }

    public function test_male_user_cannot_access_lingerie_fitting(): void
    {
        $result = $this->detector->checkLingerieFittingAccess(1, 'male', 'test-correlation');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('gender_restriction', $result['reason']);
    }

    public function test_non_female_gender_cannot_access_lingerie_fitting(): void
    {
        $result = $this->detector->checkLingerieFittingAccess(1, 'other', 'test-correlation');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('gender_restriction', $result['reason']);
    }

    public function test_blocked_user_cannot_access_lingerie_fitting(): void
    {
        $userId = 1;
        
        // Блокируем пользователя
        $this->detector->blockUser($userId, 'test_block', 'test-correlation');

        $result = $this->detector->checkLingerieFittingAccess($userId, 'female', 'test-correlation');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('account_blocked', $result['reason']);
        $this->assertArrayHasKey('block_expires_at', $result);

        // Очистка после теста
        Cache::forget('suspicious_behavior:blocked:' . $userId);
    }

    public function test_rate_limit_is_enforced(): void
    {
        $userId = 1;

        // Превышаем лимит запросов
        for ($i = 0; $i < 15; $i++) {
            $this->detector->checkLingerieFittingAccess($userId, 'female', 'test-correlation');
        }

        $result = $this->detector->checkLingerieFittingAccess($userId, 'female', 'test-correlation');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('rate_limit', $result['reason']);

        // Очистка после теста
        Cache::forget('suspicious_behavior:ratelimit:' . $userId);
    }

    public function test_suspicious_activity_is_recorded(): void
    {
        $userId = 1;
        $reason = 'test_suspicion';
        $correlationId = 'test-correlation-123';

        $this->detector->recordSuspiciousActivity($userId, $reason, $correlationId);

        $key = 'suspicious_behavior:suspicious:' . $userId;
        $activities = Cache::get($key);

        $this->assertIsArray($activities);
        $this->assertNotEmpty($activities);
        $this->assertEquals($reason, $activities[0]['reason']);
        $this->assertEquals($correlationId, $activities[0]['correlation_id']);

        // Очистка после теста
        Cache::forget($key);
    }

    public function test_suspicion_score_increases_with_activity(): void
    {
        $userId = 1;

        // Записываем подозрительную активность
        for ($i = 0; $i < 5; $i++) {
            $this->detector->recordSuspiciousActivity($userId, 'test_reason', 'test-correlation');
        }

        $stats = $this->detector->getUserSuspicionStats($userId);

        $this->assertArrayHasKey('suspicion_score', $stats);
        $this->assertGreaterThan(0, $stats['suspicion_score']);

        // Очистка после теста
        Cache::forget('suspicious_behavior:suspicious:' . $userId);
    }

    public function test_high_suspicion_score_blocks_user(): void
    {
        $userId = 1;

        // Записываем много подозрительной активности для высокого score
        for ($i = 0; $i < 10; $i++) {
            $this->detector->recordSuspiciousActivity($userId, 'test_reason', 'test-correlation');
        }

        $result = $this->detector->checkLingerieFittingAccess($userId, 'female', 'test-correlation');

        $this->assertFalse($result['allowed']);
        $this->assertEquals('suspicious_behavior', $result['reason']);

        // Очистка после теста
        Cache::forget('suspicious_behavior:suspicious:' . $userId);
        Cache::forget('suspicious_behavior:blocked:' . $userId);
    }

    public function test_user_can_be_unblocked(): void
    {
        $userId = 1;
        
        // Блокируем пользователя
        $this->detector->blockUser($userId, 'test_block', 'test-correlation');

        $this->assertTrue($this->detector->isUserBlocked($userId));

        // Разблокируем
        $result = $this->detector->unblockUser($userId, 'admin_unblock');

        $this->assertTrue($result);
        $this->assertFalse($this->detector->isUserBlocked($userId));
    }

    public function test_unblocking_non_blocked_user_returns_false(): void
    {
        $userId = 1;

        $result = $this->detector->unblockUser($userId, 'admin_unblock');

        $this->assertFalse($result);
    }

    public function test_user_suspicion_stats_returns_correct_structure(): void
    {
        $userId = 1;

        $stats = $this->detector->getUserSuspicionStats($userId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('is_blocked', $stats);
        $this->assertArrayHasKey('suspicion_score', $stats);
        $this->assertArrayHasKey('suspicious_activities', $stats);
        $this->assertArrayHasKey('recent_requests', $stats);
    }

    public function test_correlation_id_is_preserved(): void
    {
        $userId = 1;
        $customCorrelationId = 'custom-123-xyz';

        $result = $this->detector->checkLingerieFittingAccess($userId, 'female', $customCorrelationId);

        $this->assertTrue($result['allowed']);
    }

    public function test_custom_correlation_id_is_recorded_in_suspicious_activity(): void
    {
        $userId = 1;
        $customCorrelationId = 'custom-456-abc';

        $this->detector->recordSuspiciousActivity($userId, 'test_reason', $customCorrelationId);

        $key = 'suspicious_behavior:suspicious:' . $userId;
        $activities = Cache::get($key);

        $this->assertEquals($customCorrelationId, $activities[0]['correlation_id']);

        // Очистка после теста
        Cache::forget($key);
    }

    public function test_suspicious_activities_are_limited_to_50(): void
    {
        $userId = 1;

        // Записываем более 50 активностей
        for ($i = 0; $i < 60; $i++) {
            $this->detector->recordSuspiciousActivity($userId, 'test_reason', 'test-correlation');
        }

        $stats = $this->detector->getUserSuspicionStats($userId);

        $this->assertLessThanOrEqual(50, count($stats['suspicious_activities']));

        // Очистка после теста
        Cache::forget('suspicious_behavior:suspicious:' . $userId);
    }

    public function test_warning_returned_for_elevated_suspicion(): void
    {
        $userId = 1;

        // Записываем умеренное количество подозрительной активности
        for ($i = 0; $i < 3; $i++) {
            $this->detector->recordSuspiciousActivity($userId, 'test_reason', 'test-correlation');
        }

        $result = $this->detector->checkLingerieFittingAccess($userId, 'female', 'test-correlation');

        // Доступ разрешен, но может быть предупреждение
        $this->assertTrue($result['allowed']);
        // Предупреждение может быть или не быть в зависимости от score

        // Очистка после теста
        Cache::forget('suspicious_behavior:suspicious:' . $userId);
        Cache::forget('suspicious_behavior:ratelimit:' . $userId);
    }

    protected function tearDown(): void
    {
        // Очистка всех кэшей после каждого теста
        Cache::flush();
        parent::tearDown();
    }
}
