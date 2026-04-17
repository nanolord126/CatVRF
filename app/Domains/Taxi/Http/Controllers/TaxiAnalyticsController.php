<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

final class TaxiAnalyticsController extends Controller
{
    public function __construct(
        private readonly TaxiAnalyticsService $analyticsService,
    ) {}

    public function aggregateDailyAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $analytics = $this->analyticsService->aggregateDailyAnalytics(
            date: Carbon::parse($validated['date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    public function aggregateDriverAnalytics(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $analytics = $this->analyticsService->aggregateDriverAnalytics(
            driverId: $driverId,
            date: Carbon::parse($validated['date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $analytics = $this->analyticsService->getRevenueAnalytics(
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    public function getDriverPerformanceReport(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $report = $this->analyticsService->getDriverPerformanceReport(
            driverId: $driverId,
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function predictDemand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $prediction = $this->analyticsService->predictDemand(
            date: Carbon::parse($validated['date']),
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'prediction' => $prediction,
        ]);
    }
}
