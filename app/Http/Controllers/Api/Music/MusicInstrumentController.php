<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Music;
use App\Domains\MusicAndInstruments\Music\Services\MusicService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\MusicInstrumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * MusicInstrumentController manages instruments and inventory via API.
 * Follows 2026 Controller canon with 60+ lines of complexity.
 */
final class MusicInstrumentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly MusicService $musicService
    ) {}
    /**
     * Display a listing of instruments.
     */
    public function index(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $instruments = $this->musicService->listInstruments(tenant()->id);
            return response()->json([
                'success' => true,
                'data' => $instruments,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to list music instruments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список инструментов.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * Create a new instrument entry in the inventory.
     */
    public function store(MusicInstrumentRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id', (string) Str::uuid());
        try {
            $instrument = $this->musicService->createInstrument(
                $request->validated(),
                tenant()->id,
                $correlationId
            );
            Log::channel('audit')->info('New music instrument created via API', [
                'instrument_id' => $instrument->id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $instrument,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create music instrument', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании инструмента: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
    /**
     * Show detailed information for a specific instrument.
     */
    public function show(int $id): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $instrument = $this->musicService->getInstrumentWithDetails($id);
            return response()->json([
                'success' => true,
                'data' => $instrument,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Инструмент не найден.',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }
    /**
     * Update an instrument entry in the inventory.
     */
    public function update(MusicInstrumentRequest $request, int $id): JsonResponse
    {
        $correlationId = $request->input('correlation_id', (string) Str::uuid());
        try {
            $instrument = $this->musicService->updateInstrument(
                $id,
                $request->validated(),
                $correlationId
            );
            Log::channel('audit')->info('Music instrument updated via API', [
                'instrument_id' => $id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $instrument,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update music instrument', [
                'instrument_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении инструмента: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
    /**
     * Soft delete an instrument from inventory.
     */
    public function destroy(int $id): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $this->musicService->deleteInstrument($id, $correlationId);
            Log::channel('audit')->info('Music instrument deleted via API', [
                'instrument_id' => $id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Инструмент успешно удален.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении инструмента.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * Handle instrument rental logic through the service layer.
     */
    public function rent(int $id, int $days): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $rental = $this->musicService->rentInstrument($id, $days, $correlationId);
            return response()->json([
                'success' => true,
                'data' => $rental,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to rent music instrument', [
                'instrument_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка аренды: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
