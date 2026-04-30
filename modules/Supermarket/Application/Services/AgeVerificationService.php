<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Application\DTOs\CreateReturnData;
use Modules\Supermarket\Application\DTOs\VerificationResult;
use Modules\Supermarket\Domain\Exceptions\AgeRestrictionException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class AgeVerificationService
{
    use WithAuditLogging;
    use WithTelemetry;

    private $documentVerifier;
    private $faceAgeService;
    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
        $this->documentVerifier = app(\App\Services\DocumentVerificationService::class);
        $this->faceAgeService = app(\App\Services\FaceAgeEstimationService::class);
    }

    public function checkOrder(CreateReturnData $data, User $user): VerificationResult
    {
        return $this->withSpan(
            'age_verification.check_order',
            function () use ($data, $user) {
        $restrictedItems = collect($data->items)
            ->filter(function ($item) {
                if (!($item instanceof \Modules\Supermarket\Application\DTOs\ReturnItemData)) {
                    $item = \Modules\Supermarket\Application\DTOs\ReturnItemData::fromArray($item);
                }
                $product = \Modules\Supermarket\Infrastructure\Models\Product::find($item->productId);
                return $product && $product->is_age_restricted;
            });

        if ($restrictedItems->isEmpty()) {
            return VerificationResult::passed();
        }

        if ($user->birthdate) {
            $age = Carbon::parse($user->birthdate)->diffInYears(now());
            if ($age >= 18) {
                return VerificationResult::passed('profile_birthdate');
            }
        }

        if ($user->age_verified_at && $user->age_verified_at->diffInDays(now()) < 365) {
            return VerificationResult::passed('previous_verification');
        }

            return VerificationResult::failed('required', [
                'methods' => ['passport', 'selfie', 'bankid', 'gosuslugi'],
                'restricted_items_count' => $restrictedItems->count(),
            ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'age_verification_check_order',
                userId: (string) $user->id,
            ),
        );
    }

    public function verifyWithDocument(array $documentData, User $user): bool
    {
        return $this->withSpan(
            'age_verification.verify_document',
            function () use ($documentData, $user) {
                // Fraud check before document verification
                $this->fraudControl->check([
                    'operation_type' => 'age_verification_document',
                    'vertical' => 'supermarket',
                    'user_id' => $user->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);
                try {
            $result = $this->documentVerifier->verify($documentData);

            if ($result['success'] && $result['age'] >= 18) {
                $user->update([
                    'age_verified_at' => now(),
                    'age_verification_method' => 'document',
                ]);

                Log::info('Age verification successful with document', [
                    'user_id' => $user->id,
                    'method' => 'document',
                ]);

                    return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->recordSpanException($e);
            Log::error('Age verification with document failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'age_verification_document',
                userId: (string) $user->id,
            ),
        );
    }

    public function verifyWithSelfie(string $selfiePath, User $user): bool
    {
        return $this->withSpan(
            'age_verification.verify_selfie',
            function () use ($selfiePath, $user) {
                // Fraud check before selfie verification
                $this->fraudControl->check([
                    'operation_type' => 'age_verification_selfie',
                    'vertical' => 'supermarket',
                    'user_id' => $user->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);
                try {
            $result = $this->faceAgeService->estimateAge($selfiePath);

            if ($result['confidence'] > 0.85 && $result['age'] >= 18) {
                $user->update([
                    'age_verified_at' => now(),
                    'age_verification_method' => 'selfie',
                ]);

                Log::info('Age verification successful with selfie', [
                    'user_id' => $user->id,
                    'method' => 'selfie',
                    'confidence' => $result['confidence'],
                ]);

                    return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->recordSpanException($e);
            Log::error('Age verification with selfie failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'age_verification_selfie',
                userId: (string) $user->id,
            ),
        );
    }

    public function checkUserVerificationStatus(User $user): array
    {
        return [
            'is_verified' => $user->age_verified_at !== null,
            'verified_at' => $user->age_verified_at?->toIso8601String(),
            'is_expired' => $user->age_verified_at
                ? $user->age_verified_at->diffInDays(now()) >= 365
                : true,
            'method' => $user->age_verification_method,
        ];
    }

    public function requireVerificationForOrder(array $orderItems, User $user): bool
    {
        foreach ($orderItems as $item) {
            $product = \Modules\Supermarket\Infrastructure\Models\Product::find($item['product_id']);
            if ($product && $product->is_age_restricted) {
                $status = $this->checkUserVerificationStatus($user);
                if (!$status['is_verified'] || $status['is_expired']) {
                    return true;
                }
            }
        }

        return false;
    }
}
