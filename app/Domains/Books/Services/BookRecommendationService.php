<?php declare(strict_types=1);

namespace App\Domains\Books\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BookRecommendationService
{
    public function __construct()
    {
    }

    public function getRecommendations(int $userId, string $correlationId): \Illuminate\Database\Eloquent\Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'getRecommendations'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getRecommendations', ['domain' => __CLASS__]);

        try {
            $userBooks = DB::table('book_purchases')
                ->where('user_id', $userId)
                ->pluck('book_id')
                ->toArray();

            $recommendations = DB::table('books')
                ->whereNotIn('id', $userBooks)
                ->where('rating', '>=', 4.0)
                ->limit(10)
                ->get();

            Log::channel('audit')->info('Book recommendations generated', [
                'user_id' => $userId,
                'count' => $recommendations->count(),
                'correlation_id' => $correlationId,
            ]);

            return $recommendations;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Book recommendation generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
