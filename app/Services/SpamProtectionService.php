<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Log\Logger;
use Illuminate\Support\Str;

final readonly class SpamProtectionService
{
    private const SPAM_THRESHOLD = 10;
    private const BLACKLIST_THRESHOLD = 50;
    private const WINDOW_SECONDS = 60;

    public function __construct(
        private CacheRepository $cache,
        private Queue $queue,
        private Logger $logger,
    ) {}

    public function checkSpam(int $userId, string $action, string $ipAddress, string $correlationId): array
    {
        $userKey = "spam:user:{$userId}:{$action}";
        $ipKey = "spam:ip:{$ipAddress}:{$action}";

        $userCount = (int) $this->cache->get($userKey, 0);
        $ipCount = (int) $this->cache->get($ipKey, 0);

        $this->cache->put($userKey, $userCount + 1, self::WINDOW_SECONDS);
        $this->cache->put($ipKey, $ipCount + 1, self::WINDOW_SECONDS);

        $isSpam = $userCount > self::SPAM_THRESHOLD || $ipCount > self::SPAM_THRESHOLD;
        $isBlacklisted = $userCount > self::BLACKLIST_THRESHOLD || $ipCount > self::BLACKLIST_THRESHOLD;

        if ($isBlacklisted) {
            $this->logger->channel('audit')->warning('spam.blacklisted', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'action' => $action,
                'user_count' => $userCount,
                'ip_count' => $ipCount,
            ]);

            $this->queue->push(new \App\Domains\Beauty\Jobs\ReportSpamJob($userId, $ipAddress, $action, $correlationId));
        }

        if ($isSpam) {
            $this->logger->channel('audit')->warning('spam.detected', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'action' => $action,
                'user_count' => $userCount,
                'ip_count' => $ipCount,
            ]);
        }

        return [
            'is_spam' => $isSpam,
            'is_blacklisted' => $isBlacklisted,
            'user_count' => $userCount + 1,
            'ip_count' => $ipCount + 1,
            'threshold' => self::SPAM_THRESHOLD,
        ];
    }

    public function isIpBlacklisted(string $ipAddress): bool
    {
        return $this->cache->get("spam:blacklist:ip:{$ipAddress}", false);
    }

    public function blacklistIp(string $ipAddress, int $durationMinutes = 1440): void
    {
        $this->cache->put("spam:blacklist:ip:{$ipAddress}", true, $durationMinutes * 60);

        $this->logger->channel('audit')->info('spam.ip.blacklisted', [
            'ip_address' => $ipAddress,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    public function checkContentSpam(string $content, string $correlationId): array
    {
        $spamPatterns = [
            '/http(s)?:\/\/[^\s]+/i',
            '/\b(viagra|cialis|casino|poker|lottery)\b/i',
            '/\b(buy cheap|free money|win big)\b/i',
            '/[^\x00-\x7F]/u',
        ];

        $spamScore = 0;
        $matchedPatterns = [];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $spamScore += 25;
                $matchedPatterns[] = $pattern;
            }
        }

        $isSpam = $spamScore >= 50;

        if ($isSpam) {
            $this->logger->channel('audit')->warning('spam.content.detected', [
                'correlation_id' => $correlationId,
                'spam_score' => $spamScore,
                'matched_patterns' => $matchedPatterns,
            ]);
        }

        return [
            'is_spam' => $isSpam,
            'spam_score' => $spamScore,
            'matched_patterns' => $matchedPatterns,
        ];
    }
}
