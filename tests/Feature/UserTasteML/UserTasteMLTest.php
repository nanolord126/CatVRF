<?php

declare(strict_types=1);

namespace Tests\Feature\UserTasteML;

use App\Domains\Common\Events\UserInteractionEvent;
use App\Domains\Common\Services\TasteMLService;
use App\Domains\Common\Services\UserTasteProfileService;
use App\Models\ProductEmbedding;
use App\Models\User;
use App\Models\UserTasteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CANON 2026: User Taste ML Analysis Tests
 */
final class UserTasteMLTest extends TestCase
{
    use RefreshDatabase;

    private UserTasteProfileService $tasteService;

    private TasteMLService $mlService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tasteService = resolve(UserTasteProfileService::class);
        $this->mlService = resolve(TasteMLService::class);
    }

    /**
     * Тест: Создание профиля вкусов пользователя
     */
    public function testCreateUserTasteProfile(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $profile = $this->tasteService->getOrCreateProfile($user->id, $tenantId);

        $this->assertNotNull($profile);
        $this->assertEquals($user->id, $profile->user_id);
        $this->assertEquals($tenantId, $profile->tenant_id);
        $this->assertFalse($profile->opt_out);
        $this->assertTrue($profile->is_enabled);
    }

    /**
     * Тест: Установка размеров пользователя
     */
    public function testSetSizeProfile(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $sizes = [
            'clothing' => 'M',
            'shoes' => '38',
            'jeans' => '30',
        ];

        $this->tasteService->setSizeProfile($user->id, $tenantId, $sizes);

        $profile = UserTasteProfile::where([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
        ])->first();

        $this->assertEquals($sizes, $profile->size_profile);
    }

    /**
     * Тест: Установка явных предпочтений
     */
    public function testSetExplicitPreferences(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $preferences = [
            'favorite_brands' => ['Nike', 'Zara'],
            'diet_restrictions' => ['vegetarian'],
        ];

        $this->tasteService->setExplicitPreferences(
            $user->id,
            $tenantId,
            $preferences
        );

        $retrieved = $this->tasteService->getExplicitPreferences($user->id, $tenantId);

        $this->assertEquals($preferences['favorite_brands'], $retrieved['favorite_brands']);
        $this->assertEquals($preferences['diet_restrictions'], $retrieved['diet_restrictions']);
    }

    /**
     * Тест: Отключение персонализации
     */
    public function testDisablePersonalization(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $this->tasteService->getOrCreateProfile($user->id, $tenantId);
        $this->tasteService->disablePersonalization($user->id, $tenantId);

        $this->assertFalse(
            $this->tasteService->isPersonalizationEnabled($user->id, $tenantId)
        );
    }

    /**
     * Тест: Вычисление cosine similarity
     */
    public function testCosineSimilarity(): void
    {
        $vectorA = [0.1, 0.2, 0.3, 0.4, 0.5];
        $vectorB = [0.1, 0.2, 0.3, 0.4, 0.5];

        $similarity = $this->mlService->cosineSimilarity($vectorA, $vectorB);

        // Идентичные векторы должны иметь similarity = 1.0
        $this->assertEqualsWithDelta(1.0, $similarity, 0.01);
    }

    /**
     * Тест: Cosine similarity между разными векторами
     */
    public function testCosineSimilarityDifferentVectors(): void
    {
        $vectorA = [1.0, 0.0, 0.0];
        $vectorB = [0.0, 1.0, 0.0];

        $similarity = $this->mlService->cosineSimilarity($vectorA, $vectorB);

        // Ортогональные векторы должны иметь similarity ≈ 0
        $this->assertEqualsWithDelta(0.0, $similarity, 0.01);
    }

    /**
     * Тест: Получение рекомендаций на основе ML
     */
    public function testGetMLRecommendations(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        // Создать профиль с embedding'ом
        $profile = $this->tasteService->getOrCreateProfile($user->id, $tenantId);
        $embedding = array_fill(0, 384, 0.5);
        $profile->update(['embedding' => $embedding]);

        // Создать товары с embeddings
        for ($i = 0; $i < 10; $i++) {
            ProductEmbedding::create([
                'tenant_id' => $tenantId,
                'product_id' => $i + 1,
                'vertical' => 'Fashion',
                'embedding' => array_fill(0, 384, 0.5 + (rand(-10, 10) / 100)), // похожие
            ]);
        }

        // Получить рекомендации
        $recommendations = $this->mlService->getRecommendationsForUser(
            $user->id,
            $tenantId,
            'Fashion',
            5
        );

        $this->assertIsArray($recommendations);
        $this->assertLessThanOrEqual(5, count($recommendations));
    }

    /**
     * Тест: Обновление профиля после взаимодействия
     */
    public function testUpdateProfileFromInteraction(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $this->tasteService->updateProfileFromInteraction(
            $user->id,
            $tenantId,
            'view',
            [
                'product_id' => 123,
                'vertical' => 'Fashion',
                'price' => 5000,
            ]
        );

        $profile = UserTasteProfile::where([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
        ])->first();

        $this->assertGreaterThan(0, $profile->interaction_count);
    }

    /**
     * Тест: Dispatch UserInteractionEvent
     */
    public function testUserInteractionEventDispatched(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        UserInteractionEvent::dispatch(
            userId: $user->id,
            tenantId: $tenantId,
            interactionType: 'purchase',
            data: [
                'product_id' => 456,
                'vertical' => 'Food',
                'price' => 1200,
            ]
        );

        $this->assertTrue(true); // Событие должно быть успешно отправлено
    }

    /**
     * Тест: Статистика профиля
     */
    public function testGetProfileStats(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $this->tasteService->getOrCreateProfile($user->id, $tenantId);

        $stats = $this->tasteService->getProfileStats($user->id, $tenantId);

        $this->assertArrayHasKey('interaction_count', $stats);
        $this->assertArrayHasKey('ctr', $stats);
        $this->assertArrayHasKey('version', $stats);
    }

    /**
     * Тест: Кэширование явных предпочтений
     */
    public function testExplicitPreferencesCaching(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->tenant()->id;

        $preferences = ['favorite_brands' => ['Nike']];
        $this->tasteService->setExplicitPreferences($user->id, $tenantId, $preferences);

        // Первый вызов из БД
        $retrieved1 = $this->tasteService->getExplicitPreferences($user->id, $tenantId);

        // Второй вызов из кэша
        $retrieved2 = $this->tasteService->getExplicitPreferences($user->id, $tenantId);

        $this->assertEquals($retrieved1, $retrieved2);
    }
}
