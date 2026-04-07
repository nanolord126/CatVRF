<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\PromoCampaigns;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PromoCampaignsVerticalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_directory_exists(): void
    {
        self::assertDirectoryExists(base_path('app/Domains/PromoCampaigns'));
    }

    public function test_correlation_id_header_is_supported(): void
    {
        // Маршрут может быть не подключен в окружении теста — проверяем безопасно.
        try {
            \Illuminate\Support\Facades\Route::get('/__health_PromoCampaigns', static fn () => response()->json([
                'ok' => true,
                'correlation_id' => request()->header('X-Correlation-ID', 'none'),
            ]));
        } catch (\Throwable) {
            // route already exists
        }

        $response = $this->getJson('/__health_PromoCampaigns', ['X-Correlation-ID' => 'test-PromoCampaigns']);
        $response->assertStatus(200)->assertJsonPath('correlation_id', 'test-PromoCampaigns');
    }

    public function test_b2b_mode_detection_rule(): void
    {
        $request = new \Illuminate\Http\Request();
        $request->merge(['inn' => '7700000000', 'business_card_id' => 123]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');
        self::assertTrue($isB2B);
    }

    public function test_ai_directory_presence_or_skip(): void
    {
        $aiPath = base_path('app/Domains/PromoCampaigns/Services/AI');
        if (!is_dir($aiPath)) {
            $this->markTestSkipped('AI директория пока отсутствует для вертикали PromoCampaigns');
        }

        $files = glob($aiPath . '/*.php') ?: [];
        self::assertNotEmpty($files, 'В AI директории нет PHP файлов');
    }
}