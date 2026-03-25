<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Domains\Freelance\Models\FreelanceProject;
use App\Domains\Freelance\Models\Freelancer;
use App\Domains\Freelance\Models\ProjectProposal;
use App\Domains\Freelance\Models\ProjectMilestone;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис управления фриланс-проектами - КАНОН 2026.
 * Эскроу (Безопасная сделка), этапы (Milestones), 14% комиссия.
 */
final class FreelanceProjectService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Публикация нового проекта.
     */
    public function postProject(int $clientId, array $data, string $correlationId = ""): FreelanceProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting на публикацию проектов
        if (RateLimiter::tooManyAttempts("freelance:post_project:".$clientId, 5)) {
            throw new \RuntimeException("Too many projects posted. Verification required.", 429);
        }
        RateLimiter::hit("freelance:post_project:".$clientId, 3600);

        return $this->db->transaction(function () use ($clientId, $data, $correlationId) {
            // 2. Fraud Check - проверка клиента на наличие неоплаченных споров
            $this->fraud->check([
                "user_id" => $clientId,
                "operation_type" => "freelance_post_project",
                "correlation_id" => $correlationId
            ]);

            $project = FreelanceProject::create([
                "uuid" => (string) Str::uuid(),
                "client_id" => $clientId,
                "tenant_id" => auth()->user()->tenant_id ?? 1,
                "title" => $data["title"],
                "description" => $data["description"],
                "budget_kopecks" => $data["budget_kopecks"],
                "status" => "published",
                "correlation_id" => $correlationId,
                "tags" => array_merge($data["tags"] ?? [], ["escrow_protected:yes"])
            ]);

            $this->log->channel("audit")->info("Freelance: project published", [
                "project_id" => $project->id,
                "budget" => $data["budget_kopecks"]
            ]);

            return $project;
        });
    }

    /**
     * Принятие предложения и запуск сделки (Escrow Hold).
     */
    public function startContract(int $projectId, int $proposalId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $project = FreelanceProject::findOrFail($projectId);
        $proposal = ProjectProposal::findOrFail($proposalId);

        $this->db->transaction(function () use ($project, $proposal, $correlationId) {
            // 3. Холдирование средств в кошельке клиента (Escrow)
            $this->wallet->hold(
                userId: $project->client_id,
                amount: $project->budget_kopecks,
                type: "freelance_escrow",
                reason: "Escrow for Project #{$project->id}",
                correlationId: $correlationId
            );

            $project->update([
                "freelancer_id" => $proposal->freelancer_id,
                "status" => "in_progress",
                "active_proposal_id" => $proposalId,
                "started_at" => now()
            ]);

            $this->log->channel("audit")->info("Freelance: contract started (Escrow active)", [
                "project_id" => $projectId,
                "freelancer_id" => $proposal->freelancer_id,
                "hold_amount" => $project->budget_kopecks
            ]);
        });
    }

    /**
     * Приемка этапа и выплата (14% комиссия).
     */
    public function completeMilestone(int $milestoneId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $milestone = ProjectMilestone::with("project")->findOrFail($milestoneId);
        $project = $milestone->project;

        $this->db->transaction(function () use ($milestone, $project, $correlationId) {
            // 4. Расчет комиссии платформы (14%)
            $amount = $milestone->budget_kopecks;
            $fee = (int) ($amount * 0.14);
            $payout = $amount - $fee;

            // 5. Выплата фрилансеру из холда
            $this->wallet->releaseHold(
                userId: $project->client_id,
                amount: $amount,
                correlationId: $correlationId
            );

            $this->wallet->credit(
                userId: $project->freelancer_id,
                amount: $payout,
                type: "freelance_milestone_payout",
                reason: "Milestone #{$milestone->id} completed for Project #{$project->id}",
                correlationId: $correlationId
            );

            $milestone->update(["status" => "completed", "completed_at" => now()]);

            $this->log->channel("audit")->info("Freelance: milestone payout done", [
                "project_id" => $project->id,
                "payout" => $payout,
                "fee" => $fee
            ]);
        });
    }
}
