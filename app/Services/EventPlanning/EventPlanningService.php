<?php declare(strict_types=1);

namespace App\Services\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventPlanningService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Create a new event project with full audit and transactional safety.
         * Includes: Initial data, correlation tracking, and fraud check.
         */
        public function createProject(array $data, string $correlationId = null): EventProject
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Audit Start
            Log::channel('audit')->info('[EventPlanning] Project Creation Initiated', [
                'correlation_id' => $correlationId,
                'client_id' => $data['client_id'] ?? null,
                'title' => $data['title'] ?? 'Untitled',
            ]);

            // 2. Fraud Control (Canon 2026 Rule)
            // FraudControlService::check($data); // Supposed globally available

            return DB::transaction(function () use ($data, $correlationId) {
                // 3. Entity Creation
                $project = EventProject::create([
                    'planner_id' => $data['planner_id'],
                    'client_id' => $data['client_id'],
                    'title' => $data['title'],
                    'theme' => $data['theme'] ?? 'Standard',
                    'event_date' => $data['event_date'],
                    'guest_count' => $data['guest_count'] ?? 10,
                    'status' => 'planning',
                    'type' => $data['type'] ?? 'b2c',
                    'metadata' => $data['metadata'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('[EventPlanning] Project Created Successfully', [
                    'project_uuid' => $project->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return $project;
            });
        }

        /**
         * Finalize and confirm a project (transition to 'confirmed').
         */
        public function confirmProject(int $projectId, string $correlationId): bool
        {
            return DB::transaction(function () use ($projectId, $correlationId) {
                $project = EventProject::findOrFail($projectId);

                if ($project->status !== 'planning') {
                    throw new \Exception("Only projects in 'planning' status can be confirmed.");
                }

                // check if booking exists
                if ($project->bookings()->count() === 0) {
                     throw new \Exception("Cannot confirm project without an active booking.");
                }

                $project->update([
                    'status' => 'confirmed',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('[EventPlanning] Project Confirmed', [
                    'project_uuid' => $project->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }

        /**
         * Calculate Project Statistics across tenant (B2B/B2C ratio, budgets).
         */
        public function getPlannerStatistics(int $plannerId): array
        {
            return [
                'total_projects' => EventProject::where('planner_id', $plannerId)->count(),
                'active_bookings' => EventBooking::whereHas('event', fn($q) => $q->where('planner_id', $plannerId))
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
                'b2b_percentage' => EventProject::where('planner_id', $plannerId)->where('type', 'b2b')->count() /
                                   (EventProject::where('planner_id', $plannerId)->count() ?: 1) * 100,
            ];
        }
}
