<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AI\RecommendationEngine;
use App\Services\LogManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RecommendationEngineTest - Тестирование AI рекомендаций
 */
final class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationEngine $engine;
    private LogManager $logManager;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = app(LogManager::class);
        $this->engine = new RecommendationEngine($this->logManager);
        $this->user = User::factory()->create();
    }

    /**
     * Тест: getPersonalizedSuggestions возвращает коллекцию
     */
    public function test_get_personalized_suggestions_returns_collection(): void
    {
        $result = $this->engine->getPersonalizedSuggestions($this->user, 'education');

        $this->assertIsObject($result);
        $this->assertTrue(method_exists($result, 'count'));
    }

    /**
     * Тест: рекомендации кэшируются
     */
    public function test_recommendations_are_cached(): void
    {
        // Первый вызов
        $result1 = $this->engine->getPersonalizedSuggestions($this->user, 'events');

        // Второй вызов должен быть из кэша
        $result2 = $this->engine->getPersonalizedSuggestions($this->user, 'events');

        $this->assertEquals($result1->count(), $result2->count());
    }

    /**
     * Тест: разные типы рекомендаций
     */
    public function test_different_recommendation_types(): void
    {
        $types = ['education', 'events', 'services', 'products'];

        foreach ($types as $type) {
            $result = $this->engine->getPersonalizedSuggestions($this->user, $type);
            $this->assertIsObject($result);
        }
    }

    /**
     * Тест: cosine similarity между векторами
     */
    public function test_cosine_similarity_calculation(): void
    {
        // Используем reflection для доступа к приватному методу
        $reflection = new \ReflectionClass($this->engine);
        $method = $reflection->getMethod('cosineSimilarity');
        $method->setAccessible(true);

        $vec1 = [1, 0, 0];
        $vec2 = [1, 0, 0];

        $similarity = $method->invoke($this->engine, $vec1, $vec2);

        $this->assertEquals(1.0, $similarity);
    }

    /**
     * Тест: похожие пользователи находятся
     */
    public function test_find_similar_users(): void
    {
        $reflection = new \ReflectionClass($this->engine);
        $method = $reflection->getMethod('findSimilarUsers');
        $method->setAccessible(true);

        $this->user->update(['category_preference' => 'sports']);

        // Создаем похожего пользователя
        User::factory()->create(['category_preference' => 'sports']);

        $similar = $method->invoke($this->engine, $this->user, 5);

        $this->assertIsArray($similar);
    }

    /**
     * Тест: история курсов пользователя извлекается
     */
    public function test_get_user_enrolled_courses(): void
    {
        $reflection = new \ReflectionClass($this->engine);
        $method = $reflection->getMethod('getUserEnrolledCourses');
        $method->setAccessible(true);

        $courses = $method->invoke($this->engine, $this->user);

        $this->assertIsArray($courses);
    }
}
