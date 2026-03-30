<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StatisticsController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Get streamer earnings and statistics
         */
        public function getBloggerStats(): JsonResponse
        {
            try {
                $userId = auth()->id();

                $blogger = BloggerProfile::where('user_id', $userId)
                    ->where('tenant_id', tenant()->id)
                    ->firstOrFail();

                $streams = Stream::where('blogger_id', $userId)
                    ->where('tenant_id', tenant()->id)
                    ->get();

                // Calculate metrics
                $totalEarned = (int) $streams->sum('total_revenue');
                $totalCommission = (int) $streams->sum('platform_commission');
                $netEarnings = $totalEarned - $totalCommission;
                $totalViewers = (int) $streams->sum('view_count');
                $totalGifts = (int) $streams->sum(function ($stream) {
                    return $stream->nftGifts()->where('minting_status', 'minted')->count();
                });
                $streamsCompleted = $streams->filter(fn($s) => $s->isEnded())->count();
                $averageViewers = $streamsCompleted > 0 ? (int)($totalViewers / $streamsCompleted) : 0;

                return response()->json([
                    'data' => [
                        'total_earned' => $totalEarned,
                        'total_commission' => $totalCommission,
                        'net_earnings' => $netEarnings,
                        'wallet_balance' => $blogger->wallet_balance,
                        'total_viewers' => $totalViewers,
                        'average_viewers_per_stream' => $averageViewers,
                        'total_gifts_received' => $totalGifts,
                        'streams_completed' => $streamsCompleted,
                        'verification_status' => $blogger->verification_status,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Get blogger stats failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to fetch statistics',
                ], 500);
            }
        }

        /**
         * Get detailed stream statistics
         */
        public function getStreamStats(string $roomId): JsonResponse
        {
            try {
                $stream = Stream::where('room_id', $roomId)
                    ->where('tenant_id', tenant()->id)
                    ->with('statistics')
                    ->firstOrFail();

                $stats = $stream->statistics;

                if (!$stats) {
                    return response()->json([
                        'message' => 'Statistics not found',
                    ], 404);
                }

                return response()->json([
                    'data' => [
                        'stream_id' => $stream->id,
                        'title' => $stream->title,
                        'status' => $stream->status,
                        'started_at' => $stream->started_at,
                        'ended_at' => $stream->ended_at,
                        'duration_minutes' => $stream->duration_minutes,
                        'peak_viewers' => $stream->peak_viewers,
                        'unique_viewers' => $stats->unique_viewers,
                        'total_messages' => $stats->total_messages,
                        'total_gifts' => $stats->total_gifts,
                        'total_gifts_revenue' => $stats->total_gifts_revenue,
                        'total_products_sold' => $stats->total_products_sold,
                        'total_commerce_revenue' => $stats->total_commerce_revenue,
                        'engagement_rate' => $stats->engagement_rate,
                        'viewer_countries' => $stats->viewer_countries,
                        'traffic_sources' => $stats->traffic_sources,
                        'revenue' => [
                            'gross' => $stream->total_revenue,
                            'commission' => $stream->platform_commission,
                            'net' => $stream->total_revenue - $stream->platform_commission,
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to fetch statistics',
                ], 500);
            }
        }

        /**
         * Get platform-wide statistics (admin)
         */
        public function getPlatformStats(): JsonResponse
        {
            try {
                if (!auth()->user()->isAdmin()) {
                    return response()->json([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $streams = Stream::where('tenant_id', tenant()->id)
                    ->get();

                $totalStreams = $streams->count();
                $liveStreams = $streams->where('status', 'live')->count();
                $totalRevenue = (int) $streams->sum('total_revenue');
                $totalCommission = (int) $streams->sum('platform_commission');
                $totalViewers = (int) $streams->sum('view_count');

                return response()->json([
                    'data' => [
                        'total_streams' => $totalStreams,
                        'live_streams' => $liveStreams,
                        'total_revenue' => $totalRevenue,
                        'total_commission' => $totalCommission,
                        'total_viewers' => $totalViewers,
                        'average_viewers_per_stream' => $totalStreams > 0 ? (int)($totalViewers / $totalStreams) : 0,
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to fetch statistics',
                ], 500);
            }
        }

        /**
         * Get leaderboard (top streamers by earnings)
         */
        public function getLeaderboard(): JsonResponse
        {
            try {
                $topStreamers = BloggerProfile::where('tenant_id', tenant()->id)
                    ->where('verification_status', 'verified')
                    ->with('user')
                    ->orderByDesc('total_earned')
                    ->limit(50)
                    ->get()
                    ->map(fn($blogger) => [
                        'user_id' => $blogger->user_id,
                        'name' => $blogger->user->name,
                        'total_earned' => $blogger->total_earned,
                        'wallet_balance' => $blogger->wallet_balance,
                        'verification_status' => $blogger->verification_status,
                    ]);

                return response()->json([
                    'data' => $topStreamers,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to fetch leaderboard',
                ], 500);
            }
        }
}
