<?php

namespace App\Services\AI\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIFraudDetectionService
{
    /**
     * Analyze Review for Fake Patterns (NLP + Metadata analysis - simulated).
     * Patterns: Account age, review speed, identical phrases across accounts.
     */
    public function analyzeReview(int $reviewId, string $content, array $metadata = []): array
    {
        $probability = 0.0;
        $evidence = [];

        // Pattern 1: Similarity Check (Simulated)
        // Check if identical content exists in other reviews (plagiarism detection)
        $duplicateCount = DB::table('reviews') // Assuming reviews table from Marketplace vertical
            ->where('content', 'LIKE', '%' . substr($content, 0, 50) . '%')
            ->where('id', '!=', $reviewId)
            ->count();

        if ($duplicateCount > 0) {
            $probability += 0.6;
            $evidence['duplicate_content'] = "Content matches {$duplicateCount} other reviews.";
        }

        // Pattern 2: Bursts of reviews from New Accounts (Simulated)
        $userId = $metadata['user_id'] ?? null;
        if ($userId) {
            $user = User::query()->find($userId);
            if ($user && $user->created_at->diffInDays(now()) < 2) {
                $probability += 0.3;
                $evidence['new_account_burst'] = "Account created less than 48h ago.";
            }
        }

        // Pattern 3: sentiment mismatch (Simulated)
        // If content is negative but star rating is 5 (common for low-quality bot farm errors)
        if (($metadata['rating'] ?? 5) == 5 && (str_contains(strtolower($content), 'bad') || str_contains(strtolower($content), 'terrible'))) {
            $probability += 0.4;
            $evidence['sentiment_rating_mismatch'] = "Sentiment/Rating disparity detected.";
        }

        $probability = min($probability, 1.0);

        if ($probability > 0.5) {
            DB::table('ai_fraud_detections')->insert([
                'user_id' => $userId,
                'entity_type' => 'Review',
                'entity_id' => $reviewId,
                'probability' => $probability,
                'flag_type' => 'fake_review',
                'evidence' => json_encode($evidence),
                'status' => 'pending',
                'correlation_id' => $metadata['correlation_id'] ?? (string) Str::uuid(),
                'created_at' => now(),
            ]);
        }

        return [
            'is_suspicious' => $probability > 0.5,
            'probability' => $probability,
            'evidence' => $evidence
        ];
    }

    /**
     * Analyze Ride Patterns for GPS Spoofing / Payment Washing (Taxi-specific).
     */
    public function analyzeTaxiRide(int $rideId, array $gpsData, array $paymentData = []): array
    {
        $probability = 0.0;
        $evidence = [];

        // Logic 1: Impossible Speed (Teleportation)
        // If speed > 150 km/h in city context (Simulated)
        if (($gpsData['max_speed'] ?? 0) > 150) {
            $probability += 0.9;
            $evidence['gps_spoofing'] = "Speed exceed capability of vehicle classes.";
        }

        // Logic 2: Short Travel + High Payment (Money Laundering / Reward Wash)
        if (($gpsData['distance'] ?? 0) < 0.2 && ($paymentData['amount'] ?? 0) > 1000) {
            $probability += 0.85;
            $evidence['payment_wash'] = "High transaction value for near-zero distance.";
        }

        if ($probability > 0.6) {
           // Insert into detections...
        }

        return [
            'is_fraud' => $probability > 0.6,
            'probability' => $probability,
            'evidence' => $evidence
        ];
    }
}
