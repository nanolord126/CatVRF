<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\RecommendationService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Domains\RealEstate\DTOs\CreateListingDto;
use App\Domains\RealEstate\DTOs\UpdateListingDto;
use App\Domains\RealEstate\DTOs\PublishListingDto;
use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Events\PropertyListed;
use App\Domains\RealEstate\Domain\Events\PropertyPublished;
use App\Domains\RealEstate\Domain\Events\PropertyUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\UploadedFile;

final readonly class PropertyListingService
{
    private const MAX_IMAGES_PER_LISTING = 50;
    private const MAX_VIDEO_DURATION_SECONDS = 300;
    private const MAX_3D_MODEL_SIZE_MB = 500;
    private const IMAGE_QUALITY_THRESHOLD = 0.7;
    private const MIN_TITLE_LENGTH = 20;
    private const MAX_TITLE_LENGTH = 150;
    private const MIN_DESCRIPTION_LENGTH = 100;
    private const MAX_DESCRIPTION_LENGTH = 5000;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private RecommendationService $recommendation,
        private RealEstateDynamicPricingService $dynamicPricing,
        private RealEstateBlockchainVerificationService $blockchain,
        private RealEstatePredictiveScoringService $predictiveScoring,
        private RealEstateVirtualTourService $virtualTour,
    ) {}

    public function createListing(CreateListingDto $dto): Property
    {
        $this->fraud->check(
            userId: $dto->sellerId,
            operationType: 'property_listing_create',
            amount: (int) $dto->price,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $this->validateListingData($dto);
        $this->validateImages($dto->images);
        $this->validateVideo($dto->video);
        $this->validate3DModel($dto->tour3DModel);

        if ($dto->idempotencyKey !== null) {
            $existing = $this->idempotency->checkAndLock($dto->idempotencyKey, 'property_listing');
            if ($existing !== null) {
                return Property::findOrFail($existing['property_id']);
            }
        }

        return DB::transaction(function () use ($dto) {
            $property = Property::create([
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $dto->correlationId,
                'seller_id' => $dto->sellerId,
                'agent_id' => $dto->agentId,
                'title' => $dto->title,
                'description' => $dto->description,
                'property_type' => $dto->propertyType,
                'listing_type' => $dto->listingType,
                'price' => $dto->price,
                'currency' => $dto->currency,
                'area' => $dto->area,
                'rooms' => $dto->rooms,
                'bathrooms' => $dto->bathrooms,
                'floor' => $dto->floor,
                'total_floors' => $dto->totalFloors,
                'year_built' => $dto->yearBuilt,
                'address' => $dto->address,
                'city' => $dto->city,
                'district' => $dto->district,
                'lat' => $dto->lat,
                'lon' => $dto->lon,
                'status' => PropertyStatusEnum::DRAFT->value,
                'is_b2b' => $dto->isB2b,
                'is_featured' => false,
                'is_verified' => false,
                'blockchain_verified' => false,
                'blockchain_tx_hash' => null,
                'images' => $this->processImages($dto->images),
                'video_url' => $this->processVideo($dto->video),
                'tour_3d_url' => $this->process3DModel($dto->tour3DModel),
                'virtual_tour_enabled' => $dto->tour3DModel !== null,
                'amenities' => $dto->amenities,
                'features' => $dto->features,
                'contact_phone' => $dto->contactPhone,
                'contact_email' => $dto->contactEmail,
                'show_contact' => $dto->showContact,
                'available_from' => $dto->availableFrom,
                'available_until' => $dto->availableUntil,
                'minimum_rental_period' => $dto->minimumRentalPeriod,
                'security_deposit' => $dto->securityDeposit,
                'pet_policy' => $dto->petPolicy,
                'furnishing' => $dto->furnishing,
                'parking' => $dto->parking,
                'heating' => $dto->heating,
                'cooling' => $dto->cooling,
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'device_fingerprint' => request()->header('X-Device-Fingerprint'),
                    'listing_source' => $dto->listingSource ?? 'web',
                ],
                'tags' => array_merge($dto->tags ?? [], ['listing', 'draft']),
                'ai_generated_description' => false,
                'ai_enhanced_images' => false,
                'dynamic_pricing_enabled' => $dto->enableDynamicPricing,
                'suggested_price' => null,
                'liquidity_score' => null,
                'fraud_score' => null,
            ]);

            $this->generateAIEnhancements($property);

            Log::channel('audit')->info('Property listing created', [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'seller_id' => $dto->sellerId,
                'title' => $dto->title,
                'price' => $dto->price,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new PropertyListed($property, $dto->correlationId));

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->store($dto->idempotencyKey, 'property_listing', [
                    'property_id' => $property->id,
                ]);
            }

            return $property;
        });
    }

    public function updateListing(UpdateListingDto $dto): Property
    {
        $this->fraud->check(
            userId: $dto->sellerId,
            operationType: 'property_listing_update',
            amount: (int) $dto->price,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $property = Property::where('uuid', $dto->propertyUuid)
            ->where('tenant_id', $dto->tenantId)
            ->where('seller_id', $dto->sellerId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateListingUpdate($property, $dto);

        return DB::transaction(function () use ($dto, $property) {
            $updateData = array_filter([
                'title' => $dto->title,
                'description' => $dto->description,
                'price' => $dto->price,
                'area' => $dto->area,
                'rooms' => $dto->rooms,
                'bathrooms' => $dto->bathrooms,
                'contact_phone' => $dto->contactPhone,
                'contact_email' => $dto->contactEmail,
                'show_contact' => $dto->showContact,
                'available_from' => $dto->availableFrom,
                'amenities' => $dto->amenities,
                'features' => $dto->features,
                'images' => $dto->images !== null ? $this->processImages($dto->images) : null,
                'video_url' => $dto->video !== null ? $this->processVideo($dto->video) : null,
                'tour_3d_url' => $dto->tour3DModel !== null ? $this->process3DModel($dto->tour3DModel) : null,
                'virtual_tour_enabled' => $dto->tour3DModel !== null,
            ], fn($value) => $value !== null);

            $property->update($updateData);

            if ($dto->regenerateAI) {
                $this->generateAIEnhancements($property);
            }

            Log::channel('audit')->info('Property listing updated', [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'seller_id' => $dto->sellerId,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new PropertyUpdated($property, $dto->correlationId));

            return $property->fresh();
        });
    }

    public function publishListing(PublishListingDto $dto): Property
    {
        $this->fraud->check(
            userId: $dto->sellerId,
            operationType: 'property_listing_publish',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $property = Property::where('uuid', $dto->propertyUuid)
            ->where('tenant_id', $dto->tenantId)
            ->where('seller_id', $dto->sellerId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateListingPublish($property);

        return DB::transaction(function () use ($dto, $property) {
            $blockchainResult = null;
            if ($dto->enableBlockchainVerification) {
                $blockchainResult = $this->blockchain->verifyPropertyDocuments(
                    $property->id,
                    $dto->documents,
                    $dto->correlationId
                );
            }

            $predictiveScore = null;
            if ($dto->enablePredictiveScoring) {
                $predictiveScore = $this->predictiveScoring->calculateTransactionScore(
                    $property->id,
                    $dto->correlationId
                );
            }

            $property->update([
                'status' => PropertyStatusEnum::AVAILABLE->value,
                'published_at' => now(),
                'is_verified' => $dto->enableBlockchainVerification && $blockchainResult['verified'],
                'blockchain_verified' => $dto->enableBlockchainVerification && $blockchainResult['verified'],
                'blockchain_tx_hash' => $blockchainResult['tx_hash'] ?? null,
                'liquidity_score' => $predictiveScore['liquidity_score'] ?? null,
                'fraud_score' => $predictiveScore['fraud_score'] ?? null,
                'is_featured' => $dto->makeFeatured,
                'metadata' => array_merge($property->metadata ?? [], [
                    'published_by' => $dto->publishedBy,
                    'blockchain_verification_enabled' => $dto->enableBlockchainVerification,
                    'predictive_scoring_enabled' => $dto->enablePredictiveScoring,
                ]),
            ]);

            if ($property->dynamic_pricing_enabled) {
                $this->dynamicPricing->enableDynamicPricing($property->id, $dto->correlationId);
            }

            $this->recommendation->indexPropertyForRecommendations($property->id);

            Log::channel('audit')->info('Property listing published', [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'seller_id' => $dto->sellerId,
                'blockchain_verified' => $property->blockchain_verified,
                'liquidity_score' => $property->liquidity_score,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new PropertyPublished($property, $dto->correlationId));

            return $property->fresh();
        });
    }

    public function archiveListing(string $propertyUuid, int $tenantId, int $sellerId, string $correlationId): Property
    {
        $property = Property::where('uuid', $propertyUuid)
            ->where('tenant_id', $tenantId)
            ->where('seller_id', $sellerId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($property->status === PropertyStatusEnum::SOLD->value) {
            throw new Exception('Cannot archive sold property');
        }

        return DB::transaction(function () use ($property, $correlationId) {
            $property->update([
                'status' => PropertyStatusEnum::ARCHIVED->value,
                'archived_at' => now(),
            ]);

            Log::channel('audit')->info('Property listing archived', [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return $property->fresh();
        });
    }

    public function markAsSold(string $propertyUuid, int $tenantId, int $sellerId, string $correlationId, ?int $transactionId = null): Property
    {
        $property = Property::where('uuid', $propertyUuid)
            ->where('tenant_id', $tenantId)
            ->where('seller_id', $sellerId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($property->status !== PropertyStatusEnum::AVAILABLE->value) {
            throw new Exception('Property must be available to mark as sold');
        }

        return DB::transaction(function () use ($property, $correlationId, $transactionId) {
            $property->update([
                'status' => PropertyStatusEnum::SOLD->value,
                'sold_at' => now(),
                'transaction_id' => $transactionId,
            ]);

            $this->recommendation->removePropertyFromRecommendations($property->id);

            Log::channel('audit')->info('Property marked as sold', [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'transaction_id' => $transactionId,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return $property->fresh();
        });
    }

    private function validateListingData(CreateListingDto $dto): void
    {
        if (strlen($dto->title) < self::MIN_TITLE_LENGTH || strlen($dto->title) > self::MAX_TITLE_LENGTH) {
            throw new Exception(
                sprintf('Title must be between %d and %d characters', self::MIN_TITLE_LENGTH, self::MAX_TITLE_LENGTH)
            );
        }

        if (strlen($dto->description) < self::MIN_DESCRIPTION_LENGTH || strlen($dto->description) > self::MAX_DESCRIPTION_LENGTH) {
            throw new Exception(
                sprintf('Description must be between %d and %d characters', self::MIN_DESCRIPTION_LENGTH, self::MAX_DESCRIPTION_LENGTH)
            );
        }

        if ($dto->price <= 0) {
            throw new Exception('Price must be greater than 0');
        }

        if ($dto->area <= 0) {
            throw new Exception('Area must be greater than 0');
        }

        if ($dto->rooms <= 0) {
            throw new Exception('Rooms must be greater than 0');
        }

        if ($dto->lat < -90 || $dto->lat > 90) {
            throw new Exception('Invalid latitude');
        }

        if ($dto->lon < -180 || $dto->lon > 180) {
            throw new Exception('Invalid longitude');
        }
    }

    private function validateImages(?array $images): void
    {
        if ($images === null || count($images) === 0) {
            throw new Exception('At least one image is required');
        }

        if (count($images) > self::MAX_IMAGES_PER_LISTING) {
            throw new Exception(
                sprintf('Maximum %d images allowed', self::MAX_IMAGES_PER_LISTING)
            );
        }

        foreach ($images as $image) {
            if (!$image instanceof UploadedFile) {
                throw new Exception('Invalid image format');
            }

            if (!in_array($image->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                throw new Exception('Only JPG, PNG, and WebP images are allowed');
            }

            if ($image->getSize() > 10 * 1024 * 1024) {
                throw new Exception('Image size must be less than 10MB');
            }
        }
    }

    private function validateVideo(?UploadedFile $video): void
    {
        if ($video === null) {
            return;
        }

        if (!in_array($video->getClientOriginalExtension(), ['mp4', 'webm', 'mov'], true)) {
            throw new Exception('Only MP4, WebM, and MOV videos are allowed');
        }

        if ($video->getSize() > 500 * 1024 * 1024) {
            throw new Exception('Video size must be less than 500MB');
        }
    }

    private function validate3DModel(?UploadedFile $model): void
    {
        if ($model === null) {
            return;
        }

        if (!in_array($model->getClientOriginalExtension(), ['glb', 'gltf', 'obj'], true)) {
            throw new Exception('Only GLB, GLTF, and OBJ 3D models are allowed');
        }

        if ($model->getSize() > self::MAX_3D_MODEL_SIZE_MB * 1024 * 1024) {
            throw new Exception(
                sprintf('3D model size must be less than %dMB', self::MAX_3D_MODEL_SIZE_MB)
            );
        }
    }

    private function validateListingUpdate(Property $property, UpdateListingDto $dto): void
    {
        if ($property->status === PropertyStatusEnum::SOLD->value) {
            throw new Exception('Cannot update sold property');
        }

        if ($property->status === PropertyStatusEnum::ARCHIVED->value) {
            throw new Exception('Cannot update archived property');
        }

        if ($dto->title !== null) {
            if (strlen($dto->title) < self::MIN_TITLE_LENGTH || strlen($dto->title) > self::MAX_TITLE_LENGTH) {
                throw new Exception(
                    sprintf('Title must be between %d and %d characters', self::MIN_TITLE_LENGTH, self::MAX_TITLE_LENGTH)
                );
            }
        }

        if ($dto->price !== null && $dto->price <= 0) {
            throw new Exception('Price must be greater than 0');
        }
    }

    private function validateListingPublish(Property $property): void
    {
        if ($property->status !== PropertyStatusEnum::DRAFT->value) {
            throw new Exception('Only draft listings can be published');
        }

        if (empty($property->images) || count($property->images) === 0) {
            throw new Exception('At least one image is required to publish');
        }

        if (empty($property->title) || empty($property->description)) {
            throw new Exception('Title and description are required to publish');
        }
    }

    private function processImages(array $images): array
    {
        $processedImages = [];

        foreach ($images as $index => $image) {
            $path = $image->store('real-estate/images/' . date('Y/m/d'), 'public');
            $processedImages[] = [
                'url' => Storage::url($path),
                'thumbnail_url' => Storage::url($this->generateThumbnail($image)),
                'order' => $index,
                'is_primary' => $index === 0,
                'width' => getimagesize($image->getRealPath())[0] ?? 0,
                'height' => getimagesize($image->getRealPath())[1] ?? 0,
                'size' => $image->getSize(),
                'original_name' => $image->getClientOriginalName(),
            ];
        }

        return $processedImages;
    }

    private function processVideo(?UploadedFile $video): ?string
    {
        if ($video === null) {
            return null;
        }

        $path = $video->store('real-estate/videos/' . date('Y/m/d'), 'public');
        return Storage::url($path);
    }

    private function process3DModel(?UploadedFile $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $path = $model->store('real-estate/3d-models/' . date('Y/m/d'), 'public');
        return Storage::url($path);
    }

    private function generateThumbnail(UploadedFile $image): string
    {
        $thumbnail = \Intervention\Image\Facades\Image::make($image->getRealPath())
            ->resize(300, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 80);

        $thumbnailPath = 'real-estate/thumbnails/' . date('Y/m/d') . '/' . Str::random(40) . '.jpg';
        Storage::disk('public')->put($thumbnailPath, $thumbnail);

        return $thumbnailPath;
    }

    private function generateAIEnhancements(Property $property): void
    {
        $this->virtualTour->generateVirtualTour($property->id, $property->correlation_id);

        $suggestedPrice = $this->dynamicPricing->calculateOptimalPrice($property->id, $property->correlation_id);

        $property->update([
            'suggested_price' => $suggestedPrice,
            'ai_enhanced_images' => true,
        ]);
    }
}
