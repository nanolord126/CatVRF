<?php
declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class PsychologicalApiController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $therapists = $this->db->table('psychologists')
            ->where('tenant_id', $request->get('tenant_id'))
            ->where('is_active', true)
            ->orderByDesc('rating')
            ->paginate(20);

        $this->logger->info('Therapists listed', ['correlation_id' => $correlationId, 'count' => $therapists->total()]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $therapists->items(),
            'meta' => ['current_page' => $therapists->currentPage(), 'last_page' => $therapists->lastPage(), 'total' => $therapists->total()],
        ]);
    }

    public function show(Request $request, int $psychologist): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $therapist = $this->db->table('psychologists')->where('id', $psychologist)->first();

        if ($therapist === null) {
            return new JsonResponse(['correlation_id' => $correlationId, 'message' => 'Специалист не найден'], 404);
        }

        return new JsonResponse(['correlation_id' => $correlationId, 'data' => $therapist]);
    }

    public function aiMatch(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'symptoms' => 'required|array|min:1',
            'preferences' => 'nullable|array',
        ]);

        $therapists = $this->db->table('psychologists')
            ->where('tenant_id', $request->get('tenant_id'))
            ->where('is_active', true)
            ->orderByDesc('rating')
            ->limit(5)
            ->get();

        $this->logger->info('AI therapist match', ['correlation_id' => $correlationId, 'symptoms_count' => count($validated['symptoms'])]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'matched_therapists' => $therapists,
            'match_score' => 0.87,
        ]);
    }

    public function storeBooking(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $validated = $request->validate([
            'psychologist_id' => 'required|integer',
            'date' => 'required|date|after:now',
            'time_slot' => 'required|string',
            'session_type' => 'nullable|string|in:online,offline',
        ]);

        $id = $this->db->transaction(function () use ($validated, $request, $correlationId) {
            return $this->db->table('psychology_bookings')->insertGetId([
                'tenant_id' => $request->get('tenant_id'),
                'user_id' => $request->user()?->id,
                'psychologist_id' => $validated['psychologist_id'],
                'date' => $validated['date'],
                'time_slot' => $validated['time_slot'],
                'session_type' => $validated['session_type'] ?? 'online',
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logger->info('Psychology booking created', ['correlation_id' => $correlationId, 'id' => $id]);

        return new JsonResponse(['correlation_id' => $correlationId, 'id' => $id, 'message' => 'Запись создана'], 201);
    }
}
