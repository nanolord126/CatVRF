<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ComplianceIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * ComplianceController
 * Handles regulatory integrations management.
 */
final class ComplianceController extends Controller
{
    /**
     * Show the integrations list.
     */
    public function index()
    {
        $integrations = ComplianceIntegration::all(); // Scoped by global tenant scope
        
        return view('account.integrations.index', compact('integrations'));
    }

    /**
     * Test a specific integration type.
     */
    public function test(Request $request, string $type): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        $token = $request->input('api_token');
        $inn = $request->input('inn');

        if (!$token || !$inn) {
            return response()->json([
                'success' => false,
                'message' => 'ИНН и API токен обязательны для тестирования.',
                'correlation_id' => $correlationId
            ], 422);
        }

        try {
            Log::channel('audit')->info('Testing compliance integration', [
                'type' => $type,
                'inn' => $inn,
                'correlation_id' => $correlationId
            ]);

            $result = $this->performApiTest($type, $inn, $token);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'Подключение успешно проверено.' : 'Не удалось подключиться.'),
                'correlation_id' => $correlationId
            ]);

        } catch (Throwable $e) {
            Log::error('Compliance test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при тестировании подключения: ' . $e->getMessage(),
                'correlation_id' => $correlationId
            ], 500);
        }
    }

    /**
     * Connect or update integration.
     */
    public function connect(Request $request, string $type): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        
        $request->validate([
            'inn' => 'required|string|size:12',
            'api_token' => 'required|string',
        ], [
            'inn.size' => 'ИНН должен состоять из 12 цифр.',
            'inn.required' => 'ИНН обязателен.',
            'api_token.required' => 'API Токен обязателен.',
        ]);

        return DB::transaction(function () use ($request, $type, $correlationId) {
            try {
                $integration = ComplianceIntegration::updateOrCreate(
                    [
                        'tenant_id' => tenant('id'),
                        'type' => $type,
                    ],
                    [
                        'inn' => $request->input('inn'),
                        'correlation_id' => $correlationId,
                        'status' => 'connected',
                        'last_checked_at' => now(),
                        'error_message' => null,
                    ]
                );

                // Use the mutator
                $integration->setApiTokenAttribute($request->input('api_token'));
                $integration->save();

                Log::channel('audit')->info('Compliance integration connected', [
                    'id' => $integration->id,
                    'type' => $type,
                    'correlation_id' => $correlationId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Интеграция успешно подключена.',
                    'correlation_id' => $correlationId
                ]);

            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to connect compliance integration', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при сохранении интеграции.',
                    'correlation_id' => $correlationId
                ], 500);
            }
        });
    }

    /**
     * Disconnect/Delete integration.
     */
    public function disconnect(string $type): JsonResponse
    {
        $correlationId = (string) Str::uuid();

        try {
            ComplianceIntegration::where('type', $type)->delete();

            Log::channel('audit')->info('Compliance integration disconnected', [
                'type' => $type,
                'correlation_id' => $correlationId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Интеграция успешно отключена.',
                'correlation_id' => $correlationId
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении интеграции.',
                'correlation_id' => $correlationId
            ], 500);
        }
    }

    /**
     * Simulation of actual API tests for different types.
     */
    private function performApiTest(string $type, string $inn, string $token): array
    {
        switch ($type) {
            case 'honest_sign':
                // Simulation of Honest Sign API call
                // https://честныйзнак.рф/dev/api/
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-INN' => $inn
                ])->get('https://api.crpt.ru/api/v3/true-api/auth/profile');
                
                // For demo/dev purposes, let's assume successful if token is 'test_token'
                if ($token === 'test_token') {
                    return ['success' => true];
                }

                if ($response->successful()) {
                    return ['success' => true];
                }
                
                return [
                    'success' => false, 
                    'message' => 'Ошибка авторизации в Честный ЗНАК. Проверьте токен и ИНН.'
                ];

            case 'mercury':
                // Simulation of Mercury (VetIS) API
                if ($token === 'test_mercury') return ['success' => true];
                
                $mercury = app(\App\Services\Compliance\MercuryService::class);
                $result = $mercury->verifyVsd('test_vsd', $token);
                
                return [
                    'success' => $result, 
                    'message' => $result ? 'ВетИС.Меркурий подключен.' : 'Не удалось подключиться к ВетИС.'
                ];

            case 'mdlp':
                // Simulation of MDLP (Medical) API
                if ($token === 'test_mdlp') return ['success' => true];
                
                $mdlp = app(\App\Services\Compliance\MdlpService::class);
                $result = $mdlp->verifyKiz('test_kiz', $token);
                
                return [
                    'success' => $result, 
                    'message' => $result ? 'Аптечная система МДЛП подключена.' : 'Ошибка авторизации в МДЛП.'
                ];

            default:
                return ['success' => false, 'message' => 'Неизвестный тип интеграции.'];
        }
    }
}
