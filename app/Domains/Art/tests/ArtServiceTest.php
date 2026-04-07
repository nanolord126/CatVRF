<?php
declare(strict_types=1);

namespace Tests\Domains\Art;

use Illuminate\Config\Repository as ConfigRepository;

use App\Domains\Art\Models\Artist;
use App\Domains\Art\Models\Project;
use App\Domains\Art\Services\AIArtConstructor;
use App\Domains\Art\Services\ArtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ArtServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config->get()->set('fraud.ml.skip', true);
        $this->config->get()->set('art.integrations.skip_insights', true);

        Artisan::call('migrate', [
            '--path' => 'app/Domains/Art/database/migrations/art',
            '--force' => true,
        ]);
    }

    public function testB2BProjectCreationPersistsCorrelationId(): void
    {
        $artist = Artist::factory()->create();
        $service = $this->app->make(ArtService::class);

        $project = $service->createProject([
            'artist_id' => $artist->id,
            'title' => 'Визуальный кейс',
            'brief' => 'Подбор стиля для корпоративного клиента',
            'budget_cents' => 150_000,
            'inn' => '7701234567',
            'business_card_id' => 'B2B-001',
            'tenant_id' => $artist->tenant_id,
            'correlation_id' => (string) Str::uuid(),
        ]);

        $this->assertNotEmpty($project->correlation_id);
        $this->assertSame('b2b', $project->mode);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'tenant_id' => $artist->tenant_id,
            'mode' => 'b2b',
        ]);
    }

    public function testAiConstructorReturnsNonEmptySuggestions(): void
    {
        $constructor = $this->app->make(AIArtConstructor::class);
        $photo = UploadedFile::fake()->image('art.jpg');

        $payload = $constructor->analyzePhotoAndRecommend($photo, [
            'tenant_id' => 1,
            'user_id' => 1,
            'estimated_budget_cents' => 12_000,
        ]);

        $this->assertTrue($payload['success']);
        $this->assertNotEmpty($payload['suggestions']);
        $this->assertArrayHasKey('correlation_id', $payload);
    }

    public function testRecordReviewStoresCorrelationAndRating(): void
    {
        $project = Project::factory()->create();
        $service = $this->app->make(ArtService::class);

        $review = $service->recordReview($project, [
            'artist_id' => $project->artist_id,
            'user_id' => 42,
            'rating' => 5,
            'comment' => 'Всё понравилось',
            'tenant_id' => $project->tenant_id,
            'correlation_id' => (string) Str::uuid(),
        ]);

        $this->assertSame(5, $review->rating);
        $this->assertNotEmpty($review->correlation_id);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'tenant_id' => $project->tenant_id,
            'user_id' => 42,
        ]);
    }
}
