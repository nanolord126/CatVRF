<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Common\AiUserTelemetry;
use Illuminate\Support\Str;

/**
 * AI-рекомендации тестовые данные (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class AiRecommendationsSeeder extends Seeder
{
    /**
     * Run the database seeds for testing AI Recommendations.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create(['email' => 'test_user@catvrf.io']);
        $corrId = (string) Str::uuid();

        // 1. Seed some behavioral telemetry to prime the recommendation engine
        $behaviors = [
            ['event' => 'view', 'cat' => 'Veterinary', 'id' => 101],
            ['event' => 'click', 'cat' => 'Veterinary', 'id' => 101],
            ['event' => 'view', 'cat' => 'Pet Grooming', 'id' => 202],
            ['event' => 'search', 'cat' => 'Pet Grooming', 'id' => 0],
        ];

        foreach ($behaviors as $b) {
            AiUserTelemetry::create([
                'user_id' => $user->id,
                'event_type' => $b['event'],
                'entity_type' => $b['id'] ? 'App\Models\Service' : 'SearchQuery',
                'entity_id' => $b['id'],
                'category' => $b['cat'],
                'payload' => ['mock' => true],
                'correlation_id' => $corrId,
            ]);
            
            // Also push to Redis if available, though RecommendationService might handle it
            try {
                if (config('database.redis.client') !== 'mock' && class_exists('Redis')) {
                    \Illuminate\Support\Facades\Redis::zincrby("user:{$user->id}:affinity", 1, $b['cat']);
                    if ($b['id']) {
                        \Illuminate\Support\Facades\Redis::lpush("user:{$user->id}:recent_views", "{$b['cat']} service {$b['id']}");
                    }
                }
            } catch (\Exception $e) {
                // Ignore redis errors in seeder if driver not configured
            }
        }
    }
}
