<?php
declare(strict_types=1);

namespace App\Domains\Art\Services;

use App\Domains\Art\Events\ProjectCreated;
use App\Domains\Art\Models\Artwork;
use App\Domains\Art\Models\Project;
use App\Domains\Art\Models\Review;
use App\Services\AI\DemandForecastService;
use App\Services\Fraud\FraudMLService;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class ArtService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly FraudMLService $fraudML,
        private readonly RecommendationService $recommendation,
        private readonly DemandForecastService $demandForecast,
    )
    {
    }

    public function createProject(array $payload): Project
    {
        $correlationId = $payload['correlation_id'] ?? (string) Str::uuid();
        $tenantId = $this->resolveTenantId($payload['tenant_id'] ?? null);
        $audience = $this->determineAudience($payload);

        $userId = (int) ($payload['user_id'] ?? $payload['artist_id'] ?? 0);
        $budget = (int) ($payload['budget_cents'] ?? 0);

        $this->fraudControl->check(
            $userId,
            'art_project_create',
            $budget,
            $payload['ip'] ?? null,
            $payload['device_fingerprint'] ?? null,
            $correlationId,
        );
        $this->scoreFraudMl($userId, 'art_project_create', $budget, $payload, $correlationId);
        $this->enforceRateLimit($tenantId, $audience, $correlationId);

        $project = DB::transaction(function () use ($payload, $correlationId, $tenantId, $audience): Project {
            $project = Project::query()->create([
                'uuid' => $payload['uuid'] ?? (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'business_group_id' => $payload['business_group_id'] ?? null,
                'artist_id' => $payload['artist_id'],
                'title' => $payload['title'],
                'brief' => $payload['brief'] ?? 'Not provided',
                'budget_cents' => (int) ($payload['budget_cents'] ?? 0),
                'status' => $payload['status'] ?? 'draft',
                'mode' => $audience,
                'deadline_at' => isset($payload['deadline_at']) ? Carbon::parse($payload['deadline_at']) : Carbon::now()->addWeeks(2),
                'preferences' => $payload['preferences'] ?? [],
                'tags' => $payload['tags'] ?? [],
                'meta' => $payload['meta'] ?? [],
            ]);

            Log::channel('audit')->info('Art project created', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'audience' => $audience,
                'project_id' => $project->id,
            ]);

            ProjectCreated::dispatch($project, $correlationId);

            return $project;
        });

        $this->enrichWithInsights($project, $userId, $audience, $correlationId);

        return $project;
    }

    public function addArtwork(Project $project, array $data): Artwork
    {
        $correlationId = $data['correlation_id'] ?? (string) Str::uuid();
        $userId = (int) ($data['user_id'] ?? $project->artist_id);
        $amount = (int) ($data['price_cents'] ?? 0);

        $this->fraudControl->check(
            $userId,
            'artwork_attach',
            $amount,
            $data['ip'] ?? null,
            $data['device_fingerprint'] ?? null,
            $correlationId,
        );
        $this->scoreFraudMl($userId, 'artwork_attach', $amount, $data, $correlationId);
        $this->enforceRateLimit($project->tenant_id, $project->mode, $correlationId);

        $artwork = DB::transaction(function () use ($project, $data, $correlationId): Artwork {
            $artwork = $project->artworks()->create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'tenant_id' => $project->tenant_id,
                'business_group_id' => $project->business_group_id,
                'artist_id' => $project->artist_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'price_cents' => (int) ($data['price_cents'] ?? 0),
                'is_visible' => $data['is_visible'] ?? true,
                'tags' => $data['tags'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);

            Log::channel('audit')->info('Artwork attached to project', [
                'correlation_id' => $correlationId,
                'project_id' => $project->id,
                'artwork_id' => $artwork->id,
            ]);

            return $artwork;
        });

        return $artwork;
    }

    public function recordReview(Project $project, array $data): Review
    {
        $correlationId = $data['correlation_id'] ?? (string) Str::uuid();
        $userId = (int) ($data['user_id'] ?? $project->artist_id);

        $this->fraudControl->check(
            $userId,
            'art_review',
            (int) (($data['rating'] ?? 0) * 100),
            $data['ip'] ?? null,
            $data['device_fingerprint'] ?? null,
            $correlationId,
        );
        $this->scoreFraudMl($userId, 'art_review', (int) (($data['rating'] ?? 0) * 100), $data, $correlationId);

        $review = DB::transaction(function () use ($project, $data, $correlationId): Review {
            $review = $project->reviews()->create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'tenant_id' => $project->tenant_id,
                'business_group_id' => $project->business_group_id,
                'artist_id' => $project->artist_id,
                'user_id' => $data['user_id'] ?? null,
                'rating' => (int) ($data['rating'] ?? 5),
                'comment' => $data['comment'] ?? 'Спасибо за работу',
                'tags' => $data['tags'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);

            Log::channel('audit')->info('Project review saved', [
                'correlation_id' => $correlationId,
                'project_id' => $project->id,
                'review_id' => $review->id,
            ]);

            return $review;
        });

        return $review;
    }

    private function determineAudience(array $payload): string
    {
        if (!empty($payload['mode']) && in_array($payload['mode'], ['b2b', 'b2c'], true)) {
            return $payload['mode'];
        }

        if (!empty($payload['inn']) || !empty($payload['business_card_id'])) {
            return 'b2b';
        }

        return 'b2c';
    }

    private function enforceRateLimit(int $tenantId, string $audience, string $correlationId): void
    {
        $key = "art:{$audience}:tenant:{$tenantId}:correlation:{$correlationId}";
        $allowed = RateLimiter::attempt($key, $perMinute = 10, static function (): void {
        }, 60);

        if (!$allowed) {
            Log::channel('fraud_alert')->warning('Art vertical rate limit hit', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'audience' => $audience,
            ]);

            throw new ThrottleRequestsException('Rate limit exceeded for art operations.');
        }
    }

    private function resolveTenantId(?int $tenantId): int
    {
        if ($tenantId !== null) {
            return $tenantId;
        }

        if (function_exists('tenant') && tenant()) {
            return (int) tenant()->id;
        }

        $request = app()->bound('request') ? app('request') : null;
        if ($request && $request->user() && isset($request->user()->tenant_id)) {
            return (int) $request->user()->tenant_id;
        }

        return (int) config('app.tenant_id', 0);
    }

    private function scoreFraudMl(int $userId, string $operation, int $amount, array $payload, string $correlationId): void
    {
        if (config('fraud.ml.skip', false)) {
            return;
        }

        $decision = $this->fraudML->scoreOperation(
            userId: $userId,
            operationType: $operation,
            amount: $amount,
            ipAddress: (string) ($payload['ip'] ?? request()->ip()),
            deviceFingerprint: $payload['device_fingerprint'] ?? null,
            context: [
                'tenant_id' => $payload['tenant_id'] ?? null,
                'business_group_id' => $payload['business_group_id'] ?? null,
                'tags' => $payload['tags'] ?? [],
            ],
            correlationId: $correlationId,
        );

        if (($decision['decision'] ?? 'allow') !== 'allow') {
            Log::channel('fraud_alert')->warning('FraudML blocked art operation', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'operation' => $operation,
                'score' => $decision['score'] ?? null,
                'decision' => $decision['decision'] ?? null,
            ]);

            throw new \RuntimeException('FraudML rejected operation');
        }
    }

    private function enrichWithInsights(Project $project, int $userId, string $audience, string $correlationId): void
    {
        if (config('art.integrations.skip_insights', false)) {
            return;
        }

        try {
            $recommendations = $this->recommendation->getForUser(
                userId: $userId,
                vertical: 'art',
                context: [
                    'tenant_id' => $project->tenant_id,
                    'geo_hash' => $project->tags['geo'] ?? null,
                    'audience' => $audience,
                ],
                correlationId: $correlationId,
            );

            $forecast = $this->demandForecast->forecastForItem(
                itemId: $project->id,
                dateFrom: Carbon::now(),
                dateTo: Carbon::now()->addDays(7),
                context: ['use_for_critical' => false],
                correlationId: $correlationId,
            );

            $project->forceFill([
                'meta' => array_merge($project->meta ?? [], [
                    'recommendations' => $recommendations->take(5)->toArray(),
                    'forecast' => $forecast['forecast'] ?? [],
                    'forecast_model' => $forecast['model_version'] ?? null,
                ]),
            ])->save();
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('Art insights enrichment failed', [
                'correlation_id' => $correlationId,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
