<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use App\Services\Tenancy\TenantQuotaPlanService;
use App\Services\Tenancy\TenantResourceLimiterService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

/**
 * Tenant Onboarding Service
 * 
 * Handles automatic tenant creation with:
 * - Database migrations
 * - Vertical-specific setup
 * - Default resources
 * - Wallet creation
 * - Initial configuration
 */
final readonly class TenantOnboardingService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly TenantQuotaPlanService $quotaPlanService,
    ) {}

    /**
     * Create new tenant with full setup
     */
    public function createTenant(array $data): Tenant
    {
        return $this->db->transaction(function () use ($data): Tenant {
            // Determine quota plan
            $quotaPlan = $data['quota_plan'] ?? 'free';

            // 1. Create tenant
            $tenant = Tenant::create([
                'id' => $data['id'] ?? (string) Str::uuid(),
                'name' => $data['name'],
                'type' => $data['type'] ?? 'business',
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'inn' => $data['inn'] ?? null,
                'kpp' => $data['kpp'] ?? null,
                'ogrn' => $data['ogrn'] ?? null,
                'legal_entity_type' => $data['legal_entity_type'] ?? 'OOO',
                'legal_address' => $data['legal_address'] ?? null,
                'actual_address' => $data['actual_address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_verified' => $data['is_verified'] ?? false,
                'timezone' => $data['timezone'] ?? 'Europe/Moscow',
                'correlation_id' => $data['correlation_id'] ?? Str::uuid()->toString(),
                'tags' => $data['tags'] ?? [],
                'meta' => array_merge($data['meta'] ?? [], [
                    'quota_plan' => $quotaPlan,
                    'onboarded_at' => now()->toIso8601String(),
                ]),
            ]);

            // 2. Apply quota plan to tenant
            $this->quotaPlanService->applyPlan($tenant->id, $quotaPlan);

            // 3. Run tenant-specific migrations
            $this->runTenantMigrations($tenant->id);

            // 4. Create default wallet
            $this->createDefaultWallet($tenant->id);

            // 5. Set custom quotas if provided (overrides plan)
            if (!empty($data['quotas'])) {
                $this->setCustomQuotas($tenant->id, $data['quotas']);
            }

            // 6. Initialize vertical configurations
            $this->initializeVerticals($tenant->id, $data['verticals'] ?? []);

            // 7. Create business group if B2B
            if (!empty($data['business_group'])) {
                $this->createBusinessGroup($tenant->id, $data['business_group']);
            }

            $this->logger->channel('tenant')->info('Tenant created successfully', [
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'type' => $tenant->type,
                'quota_plan' => $quotaPlan,
                'correlation_id' => $tenant->correlation_id,
            ]);

            return $tenant;
        });
    }

    /**
     * Run migrations for new tenant
     */
    private function runTenantMigrations(string $tenantId): void
    {
        try {
            // Set tenant context
            app('tenant.context')->setTenant($tenantId);

            // Run shared migrations
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations',
            ]);

            // Run vertical-specific migrations
            $verticals = $this->config->get('tenant.verticals', ['Medical', 'Beauty', 'Food', 'Delivery']);
            
            foreach ($verticals as $vertical) {
                $migrationPath = "database/migrations/verticals/{$strtolower($vertical)}";
                
                if (is_dir(database_path($migrationPath))) {
                    Artisan::call('migrate', [
                        '--force' => true,
                        '--path' => $migrationPath,
                    ]);
                }
            }

            $this->logger->channel('tenant')->info('Tenant migrations completed', [
                'tenant_id' => $tenantId,
                'verticals' => $verticals,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('tenant')->error('Tenant migrations failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Clear tenant context
            app('tenant.context')->clearTenant();
        }
    }

    /**
     * Create default wallet for tenant
     */
    private function createDefaultWallet(string $tenantId): void
    {
        $this->db->table('wallets')->insert([
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'business_group_id' => null,
            'user_id' => null,
            'type' => 'main',
            'balance_kopecks' => 0,
            'currency' => 'RUB',
            'is_active' => true,
            'metadata' => json_encode(['is_default' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logger->channel('tenant')->info('Default wallet created', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Set custom resource quotas (overrides plan quotas)
     */
    private function setCustomQuotas(string $tenantId, array $customQuotas): void
    {
        $limiter = app(TenantResourceLimiterService::class);

        foreach ($customQuotas as $resource => $quota) {
            $limiter->setCustomQuota($resource, $tenantId, $quota);
        }

        $this->logger->channel('tenant')->info('Custom quotas set (overrides plan)', [
            'tenant_id' => $tenantId,
            'custom_quotas' => $customQuotas,
        ]);
    }

    /**
     * Upgrade tenant quota plan
     */
    public function upgradeQuotaPlan(string $tenantId, string $newPlan): bool
    {
        return $this->quotaPlanService->upgradePlan($tenantId, $newPlan);
    }

    /**
     * Initialize vertical configurations
     */
    private function initializeVerticals(string $tenantId, array $enabledVerticals): void
    {
        if (empty($enabledVerticals)) {
            $enabledVerticals = $this->config->get('tenant.default_verticals', ['Medical', 'Beauty']);
        }

        foreach ($enabledVerticals as $vertical) {
            $this->db->table('tenant_verticals')->insert([
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'is_enabled' => true,
                'configuration' => json_encode($this->getVerticalDefaultConfig($vertical)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->logger->channel('tenant')->info('Verticals initialized', [
            'tenant_id' => $tenantId,
            'verticals' => $enabledVerticals,
        ]);
    }

    /**
     * Get default configuration for vertical
     */
    private function getVerticalDefaultConfig(string $vertical): array
    {
        return $this->config->get("verticals.{$vertical}.default_config", []);
    }

    /**
     * Create business group
     */
    private function createBusinessGroup(string $tenantId, array $groupData): void
    {
        BusinessGroup::create([
            'tenant_id' => $tenantId,
            'name' => $groupData['name'],
            'inn' => $groupData['inn'] ?? null,
            'kpp' => $groupData['kpp'] ?? null,
            'legal_address' => $groupData['legal_address'] ?? null,
            'actual_address' => $groupData['actual_address'] ?? null,
            'phone' => $groupData['phone'] ?? null,
            'email' => $groupData['email'] ?? null,
            'is_active' => $groupData['is_active'] ?? true,
            'is_verified' => $groupData['is_verified'] ?? false,
            'commission_percent' => $groupData['commission_percent'] ?? 0.0,
            'correlation_id' => Str::uuid()->toString(),
            'tags' => $groupData['tags'] ?? [],
            'metadata' => $groupData['metadata'] ?? [],
        ]);

        $this->logger->channel('tenant')->info('Business group created', [
            'tenant_id' => $tenantId,
            'group_name' => $groupData['name'],
        ]);
    }

    /**
     * Deactivate tenant with data retention
     */
    public function deactivateTenant(string $tenantId, int $retentionDays = 90): bool
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return false;
        }

        $tenant->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'retention_until' => now()->addDays($retentionDays),
        ]);

        // Schedule cleanup job
        \App\Jobs\TenantCleanupJob::dispatch($tenantId)
            ->delay(now()->addDays($retentionDays));

        $this->logger->channel('tenant')->info('Tenant deactivated', [
            'tenant_id' => $tenantId,
            'retention_days' => $retentionDays,
        ]);

        return true;
    }

    /**
     * Permanently delete tenant (GDPR compliance)
     */
    public function permanentlyDeleteTenant(string $tenantId): bool
    {
        $tenant = Tenant::withTrashed()->find($tenantId);

        if (!$tenant) {
            return false;
        }

        // Anonymize all data before deletion (152-ФЗ compliance)
        $this->anonymizeTenantData($tenantId);

        // Delete tenant (soft delete first, then hard delete)
        $tenant->forceDelete();

        $this->logger->channel('tenant')->warning('Tenant permanently deleted', [
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * Anonymize tenant data before deletion
     */
    private function anonymizeTenantData(string $tenantId): void
    {
        // Anonymize tenant record
        $this->db->table('tenants')
            ->where('id', $tenantId)
            ->update([
                'name' => 'Deleted',
                'inn' => null,
                'kpp' => null,
                'ogrn' => null,
                'legal_address' => null,
                'actual_address' => null,
                'phone' => null,
                'email' => "deleted_{$tenantId}@deleted.local",
            ]);

        // Anonymize users
        $this->db->table('users')
            ->where('tenant_id', $tenantId)
            ->update([
                'name' => 'Deleted',
                'email' => $this->db->raw("CONCAT('deleted_', id, '@deleted.local')"),
                'phone' => null,
            ]);

        $this->logger->channel('tenant')->info('Tenant data anonymized', [
            'tenant_id' => $tenantId,
        ]);
    }
}
