<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Services\RTBService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * RTB (Real-Time Bidding) API Controller
 *
 * Handles OpenRTB 2.6 compliant endpoints for programmatic ad buying.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class RTBController
{
    public function __construct(
        private readonly RTBService $rtbService,
    ) {}

    /**
     * Process OpenRTB bid request
     * OpenRTB 2.6 compliant endpoint
     */
    public function bid(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
            'imp' => 'required|array|min:1',
            'imp.*.id' => 'required|string',
            'imp.*.video' => 'nullable|array',
            'imp.*.video.placement' => 'nullable|string',
            'site' => 'nullable|array',
            'site.name' => 'nullable|string',
            'device' => 'nullable|array',
            'device.ip' => 'nullable|ip',
            'device.ua' => 'nullable|string',
            'user' => 'nullable|array',
            'user.id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'id' => $request->input('id'),
                'nbr' => 'invalid_request',
                'cur' => 'RUB',
            ], 400);
        }

        try {
            $response = $this->rtbService->processBidRequest(
                request: $request->all(),
                correlationId: $request->header('X-Correlation-ID'),
            );

            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json([
                'id' => $request->input('id'),
                'nbr' => 'internal_error',
                'cur' => 'RUB',
            ], 500);
        }
    }

    /**
     * Process win notification from SSP/DSP
     */
    public function win(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|string',
            'price' => 'required|integer',
            'auction_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        try {
            $success = $this->rtbService->processWinNotification(
                notification: $request->all(),
                correlationId: $request->header('X-Correlation-ID'),
            );

            return response()->json(['success' => $success]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Track impression from RTB
     */
    public function impression(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'impression_id' => 'required|string',
            'bid_id' => 'required|string',
            'timestamp' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        try {
            $success = $this->rtbService->trackImpression(
                impressionData: $request->all(),
                correlationId: $request->header('X-Correlation-ID'),
            );

            // Return 1x1 pixel for tracking
            return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
                ->header('Content-Type', 'image/gif')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Track click from RTB
     */
    public function click(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'click_id' => 'required|string',
            'bid_id' => 'required|string',
            'impression_id' => 'required|string',
            'timestamp' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        try {
            $success = $this->rtbService->trackClick(
                clickData: $request->all(),
                correlationId: $request->header('X-Correlation-ID'),
            );

            return response()->json(['success' => $success]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
