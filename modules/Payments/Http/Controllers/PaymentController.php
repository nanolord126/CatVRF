<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Http\Requests\StorePaymentMethodRequest;
use App\Modules\Payments\Http\Requests\UpdatePaymentMethodRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching payment methods', ['tenant_id' => tenant('id')]);
            
            $methods = PaymentMethod::where('tenant_id', tenant('id'))
                ->where('is_active', true)
                ->paginate($request->input('per_page', 15));
            
            Log::info('Payment methods fetched', ['count' => $methods->count()]);
            
            return response()->json($methods);
        } catch (QueryException $e) {
            Log::error('Error fetching payment methods', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch payment methods'], 500);
        }
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        try {
            Log::info('Creating payment method', ['type' => $request->type]);
            
            $method = PaymentMethod::create([
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
                'type' => $request->type,
                'token' => $request->token,
                'is_active' => true,
                'correlation_id' => \Illuminate\Support\Str::uuid(),
            ]);
            
            Log::info('Payment method created', ['method_id' => $method->id]);
            
            return response()->json($method, 201);
        } catch (QueryException $e) {
            Log::error('Error creating payment method', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create payment method'], 500);
        }
    }

    public function show(PaymentMethod $method): JsonResponse
    {
        try {
            $this->authorize('view', $method);
            
            Log::info('Retrieving payment method', ['method_id' => $method->id]);
            
            return response()->json($method);
        } catch (\Exception $e) {
            Log::warning('Unauthorized access to payment method', ['method_id' => $method->id]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $method): JsonResponse
    {
        try {
            $this->authorize('update', $method);
            
            Log::info('Updating payment method', ['method_id' => $method->id]);
            
            $method->update($request->validated());
            
            Log::info('Payment method updated', ['method_id' => $method->id]);
            
            return response()->json($method);
        } catch (\Exception $e) {
            Log::error('Error updating payment method', ['method_id' => $method->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update payment method'], 500);
        }
    }

    public function destroy(PaymentMethod $method): JsonResponse
    {
        try {
            $this->authorize('delete', $method);
            
            Log::info('Disabling payment method', ['method_id' => $method->id]);
            
            $method->update(['is_active' => false]);
            
            Log::info('Payment method disabled', ['method_id' => $method->id]);
            
            return response()->json(['message' => 'Payment method disabled'], 200);
        } catch (\Exception $e) {
            Log::error('Error disabling payment method', ['method_id' => $method->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to disable payment method'], 500);
        }
    }

    public function setDefault(PaymentMethod $method): JsonResponse
    {
        try {
            Log::info('Setting default payment method', ['method_id' => $method->id]);
            
            PaymentMethod::where('user_id', auth()->id())
                ->where('tenant_id', tenant('id'))
                ->update(['is_default' => false]);
            
            $method->update(['is_default' => true]);
            
            Log::info('Default payment method set', ['method_id' => $method->id]);
            
            return response()->json($method);
        } catch (QueryException $e) {
            Log::error('Error setting default method', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to set default method'], 500);
        }
    }

    public function verify(PaymentMethod $method): JsonResponse
    {
        try {
            Log::info('Verifying payment method', ['method_id' => $method->id]);
            
            $method->update(['is_verified' => true]);
            
            Log::info('Payment method verified', ['method_id' => $method->id]);
            
            return response()->json($method);
        } catch (QueryException $e) {
            Log::error('Error verifying payment method', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to verify payment method'], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            Log::info('Fetching payment history', ['user_id' => auth()->id()]);
            
            $history = PaymentMethod::where('user_id', auth()->id())
                ->where('tenant_id', tenant('id'))
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));
            
            return response()->json($history);
        } catch (QueryException $e) {
            Log::error('Error fetching payment history', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch history'], 500);
        }
    }
}
