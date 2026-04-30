<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\DTOs\SupermarketOrderData;
use App\Domains\Supermarket\DTOs\VerificationResult;
use App\Domains\Supermarket\Exceptions\AgeRestrictionException;
use App\Models\User;
use Carbon\Carbon;

final readonly class AgeVerificationService
{
    public function checkOrder(SupermarketOrderData $data, User $user): VerificationResult
    {
        $restrictedItems = collect($data->items)
            ->filter(fn($item) => $item['product']->is_age_restricted ?? false);

        if ($restrictedItems->isEmpty()) {
            return new VerificationResult(true);
        }

        // Check by birthdate in profile
        if ($user->birthdate) {
            $age = Carbon::parse($user->birthdate)->diffInYears(now());
            if ($age >= 18) {
                return new VerificationResult(true, 'profile_birthdate');
            }
        }

        // Check by previous verification
        if ($user->age_verified_at && $user->age_verified_at->diffInDays(now()) < 365) {
            return new VerificationResult(true, 'previous_verification');
        }

        // Require new verification
        return new VerificationResult(
            false,
            'required',
            [
                'methods' => ['passport', 'selfie', 'bankid', 'gosuslugi'],
                'restricted_items_count' => $restrictedItems->count(),
            ]
        );
    }

    public function verifyWithDocument(array $documentData, User $user): bool
    {
        // Here you can integrate with external service (Tinkoff ID, Gosuslugi, or custom)
        // For now, basic validation
        $age = $this->calculateAgeFromDocument($documentData);

        if ($age >= 18) {
            $user->update([
                'age_verified_at' => now(),
                'age_verification_method' => 'document',
            ]);

            return true;
        }

        return false;
    }

    public function verifyWithSelfie(string $selfiePath, User $user): bool
    {
        // Integrate with AI face age estimation service
        // For now, placeholder implementation
        // In production: app(AIFaceAgeService::class)->estimateAge($selfiePath);

        $result = $this->estimateAgeFromSelfie($selfiePath);

        if ($result['confidence'] > 0.85 && $result['age'] >= 18) {
            $user->update([
                'age_verified_at' => now(),
                'age_verification_method' => 'selfie',
            ]);

            return true;
        }

        return false;
    }

    private function calculateAgeFromDocument(array $documentData): int
    {
        // Implement actual age calculation from document
        // This is a placeholder - real implementation would parse passport/birthdate
        if (isset($documentData['birthdate'])) {
            return Carbon::parse($documentData['birthdate'])->diffInYears(now());
        }

        return 0;
    }

    private function estimateAgeFromSelfie(string $selfiePath): array
    {
        // Placeholder for AI age estimation
        // In production, integrate with GigaChat Vision, Face++, or similar
        return [
            'age' => 25,
            'confidence' => 0.92,
        ];
    }
}
