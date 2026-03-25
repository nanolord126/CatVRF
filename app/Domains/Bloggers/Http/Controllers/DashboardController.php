<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Controllers;

use App\Domains\Bloggers\Models\Stream;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Get blogger dashboard overview
     */
    public function index(): JsonResponse
    {
        $profile = auth()->user()->bloggerProfile;

        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $streams = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->get();

        $currentMonthStats = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->whereBetween('ended_at', [
                $currentMonth,
                $currentMonth->copy()->endOfMonth(),
            ])
            ->get();

        $lastMonthStats = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->whereBetween('ended_at', [
                $lastMonth,
                $lastMonth->copy()->endOfMonth(),
            ])
            ->get();

        $earnings = [
            'total' => $profile->wallet_balance,
            'pending' => $profile->wallet_balance,
            'current_month' => $currentMonthStats->sum('total_revenue') - $currentMonthStats->sum('platform_commission'),
            'last_month' => $lastMonthStats->sum('total_revenue') - $lastMonthStats->sum('platform_commission'),
        ];

        $viewers = [
            'total' => $streams->sum('total_viewers'),
            'average_per_stream' => $streams->count() > 0 ? round($streams->sum('total_viewers') / $streams->count()) : 0,
            'peak' => $streams->max('peak_viewers') ?? 0,
            'current_month' => $currentMonthStats->sum('total_viewers'),
        ];

        $engagement = [
            'average_chat_messages' => $streams->count() > 0 ? round($streams->sum('chat_messages_count') / $streams->count()) : 0,
            'average_engagement_rate' => $streams->count() > 0 ? round($streams->avg('average_engagement_rate'), 2) : 0,
            'total_orders' => $this->db->table('stream_orders')->where('stream_id', $streams->pluck('id'))->count(),
            'total_gifts' => $this->db->table('nft_gifts')->where('stream_id', $streams->pluck('id'))->count(),
        ];

        $streams_info = [
            'total_streams' => $streams->count(),
            'current_month_streams' => $currentMonthStats->count(),
            'average_duration' => $streams->count() > 0 ? round($streams->avg('duration_minutes')) : 0,
            'upcoming_streams' => Stream::where('blogger_id', $profile->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->count(),
        ];

        return response()->json([
            'data' => [
                'profile' => [
                    'display_name' => $profile->display_name,
                    'rating' => $profile->rating,
                    'verification_status' => $profile->verification_status,
                    'is_featured' => $profile->is_featured,
                ],
                'earnings' => $earnings,
                'viewers' => $viewers,
                'engagement' => $engagement,
                'streams' => $streams_info,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/analytics
     * Get detailed analytics
     */
    public function analytics(): JsonResponse
    {
        $profile = auth()->user()->bloggerProfile;

        $last30days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dayStreams = Stream::where('blogger_id', $profile->id)
                ->where('status', 'ended')
                ->whereBetween('ended_at', [
                    $date,
                    $date->copy()->endOfDay(),
                ])
                ->get();

            $last30days->push([
                'date' => $date->format('Y-m-d'),
                'viewers' => $dayStreams->sum('total_viewers'),
                'revenue' => $dayStreams->sum('total_revenue'),
                'streams' => $dayStreams->count(),
            ]);
        }

        return response()->json([
            'data' => [
                'last_30_days' => $last30days,
                'trending' => [
                    'best_performing_hour' => $this->getBestPerformingHour($profile),
                    'best_day' => $this->getBestDay($profile),
                    'trending_category' => $profile->category,
                ],
            ],
        ]);
    }

    /**
     * GET /api/dashboard/recommendations
     * Get growth recommendations
     */
    public function recommendations(): JsonResponse
    {
        $profile = auth()->user()->bloggerProfile;
        $streams = Stream::where('blogger_id', $profile->id)->where('status', 'ended')->get();

        $recommendations = [];

        // Recommendation 1: Streaming frequency
        if ($streams->count() < 4) {
            $recommendations[] = [
                'title' => 'Увеличьте частоту трансляций',
                'description' => 'Блогеры с 4+ трансляциями в неделю получают на 40% больше зрителей',
                'priority' => 'high',
                'action' => 'Планируйте трансляции заранее',
            ];
        }

        // Recommendation 2: Prime time
        $avgViewersPerHour = $this->getAverageViewersPerHour($profile);
        if (!isset($avgViewersPerHour[20])) {
            $recommendations[] = [
                'title' => 'Транслируйте в пиковые часы',
                'description' => 'Трансляции в 20:00-23:00 получают на 60% больше зрителей',
                'priority' => 'high',
                'action' => 'Измените время трансляции',
            ];
        }

        // Recommendation 3: Content diversification
        if ($profile->category === 'other') {
            $recommendations[] = [
                'title' => 'Определитесь с категорией',
                'description' => 'Блогеры с чёткой категорией получают на 35% больше подписчиков',
                'priority' => 'medium',
                'action' => 'Выберите основную категорию контента',
            ];
        }

        return response()->json([
            'data' => $recommendations,
        ]);
    }

    private function getBestPerformingHour($profile): int
    {
        $streams = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->get();

        $hourlyViewers = [];
        foreach ($streams as $stream) {
            $hour = $stream->started_at?->hour;
            if ($hour !== null) {
                $hourlyViewers[$hour] = ($hourlyViewers[$hour] ?? 0) + $stream->peak_viewers;
            }
        }

        return array_key_first($hourlyViewers) ?? 20;
    }

    private function getBestDay($profile): string
    {
        $streams = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->get();

        $daylyViewers = [];
        foreach ($streams as $stream) {
            $day = $stream->started_at?->format('l');
            if ($day) {
                $daylyViewers[$day] = ($daylyViewers[$day] ?? 0) + $stream->peak_viewers;
            }
        }

        $bestDay = array_key_first($daylyViewers);

        return match ($bestDay) {
            'Monday' => 'Понедельник',
            'Tuesday' => 'Вторник',
            'Wednesday' => 'Среда',
            'Thursday' => 'Четверг',
            'Friday' => 'Пятница',
            'Saturday' => 'Суббота',
            'Sunday' => 'Воскресенье',
            default => 'Среда',
        };
    }

    private function getAverageViewersPerHour($profile): array
    {
        $streams = Stream::where('blogger_id', $profile->id)
            ->where('status', 'ended')
            ->get();

        $hourlyViewers = [];
        foreach ($streams as $stream) {
            $hour = $stream->started_at?->hour;
            if ($hour !== null) {
                if (!isset($hourlyViewers[$hour])) {
                    $hourlyViewers[$hour] = ['viewers' => 0, 'count' => 0];
                }
                $hourlyViewers[$hour]['viewers'] += $stream->peak_viewers;
                $hourlyViewers[$hour]['count']++;
            }
        }

        return $hourlyViewers;
    }
}
