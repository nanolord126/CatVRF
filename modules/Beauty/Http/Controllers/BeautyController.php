<?php

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Services\BeautyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class BeautyController extends Controller
{
    public function __construct(private BeautyService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching beauty salons', ['tenant_id' => tenant()->id, 'per_page' => $request->input('per_page', 15)]);
            
            $salons = BeautySalon::where('tenant_id', tenant()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));
            
            Log::info('Beauty salons fetched', ['count' => $salons->count()]);
            
            return response()->json($salons);
        } catch (QueryException $e) {
            Log::error('Error fetching beauty salons', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch salons'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', BeautySalon::class);
            
            Log::info('Creating beauty salon', ['name' => $request->input('name')]);
            
            $salon = $this->service->createSalon($request->all());
            
            Log::info('Beauty salon created', ['salon_id' => $salon->id]);
            
            return response()->json($salon, 201);
        } catch (\Exception $e) {
            Log::error('Error creating salon', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create salon'], 500);
        }
    }

    public function show(BeautySalon $salon): JsonResponse
    {
        try {
            Log::info('Retrieving beauty salon', ['salon_id' => $salon->id]);
            
            return response()->json($salon);
        } catch (\Exception $e) {
            Log::error('Error retrieving salon', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to retrieve salon'], 500);
        }
    }

    public function update(Request $request, BeautySalon $salon): JsonResponse
    {
        try {
            $this->authorize('update', $salon);
            
            Log::info('Updating beauty salon', ['salon_id' => $salon->id]);
            
            $updated = $this->service->updateSchedule($salon, $request->all());
            
            Log::info('Beauty salon updated', ['salon_id' => $salon->id]);
            
            return response()->json($updated);
        } catch (\Exception $e) {
            Log::error('Error updating salon', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update salon'], 500);
        }
    }

    public function destroy(BeautySalon $salon): JsonResponse
    {
        try {
            $this->authorize('delete', $salon);
            
            Log::info('Deleting beauty salon', ['salon_id' => $salon->id]);
            
            $salon->delete();
            
            Log::info('Beauty salon deleted', ['salon_id' => $salon->id]);
            
            return response()->json(['message' => 'Salon deleted'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting salon', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete salon'], 500);
        }
    }
    
    public function services(BeautySalon $salon): JsonResponse
    {
        try {
            Log::info('Fetching salon services', ['salon_id' => $salon->id]);
            
            $services = $salon->services()->get();
            
            return response()->json($services);
        } catch (QueryException $e) {
            Log::error('Error fetching salon services', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch services'], 500);
        }
    }
    
    public function availability(BeautySalon $salon, Request $request): JsonResponse
    {
        try {
            Log::info('Checking salon availability', ['salon_id' => $salon->id, 'date' => $request->input('date')]);
            
            $availability = $this->service->getAvailability($salon, $request->input('date'));
            
            return response()->json($availability);
        } catch (\Exception $e) {
            Log::error('Error checking availability', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to check availability'], 500);
        }
    }
}
