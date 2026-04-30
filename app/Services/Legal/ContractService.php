<?php

declare(strict_types=1);

namespace App\Services\Legal;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Legal\LegalContract;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use App\Models\Legal\LegalConsultation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class ContractService
{
    use WithAuditLogging;

    /**
     * Constructor injection for required dependencies.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly FraudControlService $fraud,
        private readonly PricingService $pricing,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Draft a new legal contract template or custom draft.
     */
    public function draftContract(
        User $client,
        string $title,
        string $content,
        ?LegalConsultation $consultation = null,
        ?string $correlationId = null
    ): LegalContract {
        $correlationId = $correlationId ?? (string) Str::uuid();

        // 1. Fraud Check before drafting
        $this->fraud->check((int) $client->id, 'legal_contract_draft', $this->request->ip());

        $this->logger->channel('audit')->$this->logger->info('Attempting to draft legal contract', [
            'client_id' => $client->id,
            'title' => $title,
            'correlation_id' => $correlationId,
        ]);

        // 2. Database transaction
        return $this->db->transaction(function () use ($client, $title, $content, $consultation, $correlationId) {
            $contract = LegalContract::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id ?? 0,
                'consultation_id' => $consultation?->id,
                'client_id' => $client->id,
                'title' => $title,
                'content' => $content,
                'status' => 'draft',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->$this->logger->info('Legal contract drafted successfully', [
                'contract_id' => $contract->id,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Sign a legal contract with digital signature.
     */
    public function signContract(LegalContract $contract, array $signatureData, ?string $correlationId = null): void
    {
        $correlationId = $correlationId ?? $contract->correlation_id;

        $this->logger->channel('audit')->$this->logger->info('Attempting to sign legal contract', [
            'contract_id' => $contract->id,
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($contract, $signatureData, $correlationId) {
            $contract->update([
                'status' => 'signed',
                'signed_at' => CarbonImmutable::now(),
                'digital_signature' => $signatureData,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->$this->logger->info('Legal contract signed successfully', [
                'contract_id' => $contract->id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Archive a legal contract.
     */
    public function archiveContract(LegalContract $contract, ?string $correlationId = null): void
    {
        $correlationId = $correlationId ?? $contract->correlation_id;

        $this->logger->channel('audit')->$this->logger->info('Archiving legal contract', [
            'contract_id' => $contract->id,
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($contract, $correlationId) {
            $contract->update([
                'status' => 'archived',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->$this->logger->info('Legal contract archived', [
                'contract_id' => $contract->id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get client contracts for display.
     */
    public function getClientContracts(User $client): Collection
    {
        return LegalContract::where('client_id', $client->id)
            ->with(['consultation', 'consultation.lawyer'])
            ->orderByDesc('created_at')
            ->get();
    }
}
