<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Configuration Service
 *
 * Manages inventory system configuration:
 * - Warehouse settings
 * - Inventory thresholds
 * - Validation rules
 * - Feature flags
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryConfigurationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get warehouse configuration
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Configuration
     */
    public function getWarehouseConfiguration(int $warehouseId): array
    {
        $cacheKey = "inventory_config:warehouse:{$warehouseId}";

        return Cache::remember($cacheKey, 3600, function () use ($warehouseId) {
            $config = $this->db->table('inventory_configurations')
                ->where('warehouse_id', $warehouseId)
                ->where('is_active', true)
                ->get()
                ->keyBy('config_key');

            return [
                'warehouse_id' => $warehouseId,
                'settings' => $config->map(fn ($c) => [
                    'value' => $c->config_value,
                    'type' => $c->value_type,
                    'description' => $c->description,
                ])->toArray(),
            ];
        });
    }

    /**
     * Set warehouse configuration
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $configKey  Configuration key
     * @param  mixed  $configValue  Configuration value
     * @param  string  $valueType  Value type
     * @param  string  $description  Description
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function setWarehouseConfiguration(
        int $warehouseId,
        string $configKey,
        mixed $configValue,
        string $valueType,
        string $description,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $warehouseId,
            $configKey,
            $configValue,
            $valueType,
            $description,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $existing = $this->db->table('inventory_configurations')
                ->where('warehouse_id', $warehouseId)
                ->where('config_key', $configKey)
                ->first();

            if ($existing) {
                $this->db->table('inventory_configurations')
                    ->where('id', $existing->id)
                    ->update([
                        'config_value' => is_array($configValue) ? json_encode($configValue) : $configValue,
                        'value_type' => $valueType,
                        'description' => $description,
                        'updated_by' => $userId,
                        'updated_at' => now(),
                    ]);
            } else {
                $this->db->table('inventory_configurations')->insert([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'warehouse_id' => $warehouseId,
                    'config_key' => $configKey,
                    'config_value' => is_array($configValue) ? json_encode($configValue) : $configValue,
                    'value_type' => $valueType,
                    'description' => $description,
                    'is_active' => true,
                    'tenant_id' => $tenantId,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);
            }

            Cache::forget("inventory_config:warehouse:{$warehouseId}");

            $this->logAction(
                action: 'warehouse_configuration_updated',
                entityType: 'InventoryConfiguration',
                entityId: $existing->id ?? 0,
                context: [
                    'correlation_id' => $correlationId,
                    'warehouse_id' => $warehouseId,
                    'config_key' => $configKey,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get default inventory thresholds
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Thresholds
     */
    public function getDefaultThresholds(int $tenantId): array
    {
        $cacheKey = "inventory_config:thresholds:{$tenantId}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            $thresholds = $this->db->table('inventory_thresholds')
                ->where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            return [
                'tenant_id' => $tenantId,
                'min_stock_threshold_percentage' => $thresholds->min_stock_percentage ?? 20,
                'max_stock_threshold_percentage' => $thresholds->max_stock_percentage ?? 100,
                'safety_stock_percentage' => $thresholds->safety_stock_percentage ?? 10,
                'reorder_point_percentage' => $thresholds->reorder_point_percentage ?? 25,
                'low_stock_alert_threshold' => $thresholds->low_stock_alert_percentage ?? 30,
            ];
        });
    }

    /**
     * Set default inventory thresholds
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $minStockPercentage  Min stock percentage
     * @param  int  $maxStockPercentage  Max stock percentage
     * @param  int  $safetyStockPercentage  Safety stock percentage
     * @param  int  $reorderPointPercentage  Reorder point percentage
     * @param  int  $userId  User ID
     * @return bool Success
     */
    public function setDefaultThresholds(
        int $tenantId,
        int $minStockPercentage,
        int $maxStockPercentage,
        int $safetyStockPercentage,
        int $reorderPointPercentage,
        int $userId
    ): bool {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->db->table('inventory_thresholds')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->update([
                'min_stock_percentage' => $minStockPercentage,
                'max_stock_percentage' => $maxStockPercentage,
                'safety_stock_percentage' => $safetyStockPercentage,
                'reorder_point_percentage' => $reorderPointPercentage,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

        Cache::forget("inventory_config:thresholds:{$tenantId}");

        $this->logAction(
            action: 'default_thresholds_updated',
            entityType: 'InventoryThreshold',
            entityId: 0,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }

    /**
     * Get validation rules
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Validation rules
     */
    public function getValidationRules(int $tenantId): array
    {
        $cacheKey = "inventory_config:validation_rules:{$tenantId}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            $rules = $this->db->table('inventory_validation_rules')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            return [
                'tenant_id' => $tenantId,
                'rules' => $rules->map(fn ($r) => [
                    'rule_key' => $r->rule_key,
                    'rule_type' => $r->rule_type,
                    'is_enabled' => $r->is_enabled,
                    'parameters' => json_decode($r->parameters, true),
                    'severity' => $r->severity,
                ])->toArray(),
            ];
        });
    }

    /**
     * Set validation rule
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $ruleKey  Rule key
     * @param  string  $ruleType  Rule type
     * @param  bool  $isEnabled  Is enabled
     * @param  array  $parameters  Parameters
     * @param  string  $severity  Severity
     * @param  int  $userId  User ID
     * @return bool Success
     */
    public function setValidationRule(
        int $tenantId,
        string $ruleKey,
        string $ruleType,
        bool $isEnabled,
        array $parameters,
        string $severity,
        int $userId
    ): bool {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $existing = $this->db->table('inventory_validation_rules')
            ->where('tenant_id', $tenantId)
            ->where('rule_key', $ruleKey)
            ->first();

        if ($existing) {
            $this->db->table('inventory_validation_rules')
                ->where('id', $existing->id)
                ->update([
                    'rule_type' => $ruleType,
                    'is_enabled' => $isEnabled,
                    'parameters' => json_encode($parameters),
                    'severity' => $severity,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        } else {
            $this->db->table('inventory_validation_rules')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'rule_key' => $ruleKey,
                'rule_type' => $ruleType,
                'is_enabled' => $isEnabled,
                'parameters' => json_encode($parameters),
                'severity' => $severity,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        Cache::forget("inventory_config:validation_rules:{$tenantId}");

        $this->logAction(
            action: 'validation_rule_updated',
            entityType: 'InventoryValidationRule',
            entityId: $existing->id ?? 0,
            context: [
                'correlation_id' => $correlationId,
                'rule_key' => $ruleKey,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }

    /**
     * Get feature flags
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Feature flags
     */
    public function getFeatureFlags(int $tenantId): array
    {
        $cacheKey = "inventory_config:feature_flags:{$tenantId}";

        return Cache::remember($cacheKey, 1800, function () use ($tenantId) {
            $flags = $this->db->table('inventory_feature_flags')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            return [
                'tenant_id' => $tenantId,
                'flags' => $flags->map(fn ($f) => [
                    'flag_key' => $f->flag_key,
                    'is_enabled' => $f->is_enabled,
                    'description' => $f->description,
                ])->toArray(),
            ];
        });
    }

    /**
     * Set feature flag
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $flagKey  Flag key
     * @param  bool  $isEnabled  Is enabled
     * @param  string  $description  Description
     * @param  int  $userId  User ID
     * @return bool Success
     */
    public function setFeatureFlag(
        int $tenantId,
        string $flagKey,
        bool $isEnabled,
        string $description,
        int $userId
    ): bool {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $existing = $this->db->table('inventory_feature_flags')
            ->where('tenant_id', $tenantId)
            ->where('flag_key', $flagKey)
            ->first();

        if ($existing) {
            $this->db->table('inventory_feature_flags')
                ->where('id', $existing->id)
                ->update([
                    'is_enabled' => $isEnabled,
                    'description' => $description,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        } else {
            $this->db->table('inventory_feature_flags')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'flag_key' => $flagKey,
                'is_enabled' => $isEnabled,
                'description' => $description,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        Cache::forget("inventory_config:feature_flags:{$tenantId}");

        $this->logAction(
            action: 'feature_flag_updated',
            entityType: 'InventoryFeatureFlag',
            entityId: $existing->id ?? 0,
            context: [
                'correlation_id' => $correlationId,
                'flag_key' => $flagKey,
                'is_enabled' => $isEnabled,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }

    /**
     * Check if feature is enabled
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $flagKey  Flag key
     * @return bool Is enabled
     */
    public function isFeatureEnabled(int $tenantId, string $flagKey): bool
    {
        $flags = $this->getFeatureFlags($tenantId);

        return collect($flags['flags'])
            ->firstWhere('flag_key', $flagKey)['is_enabled'] ?? false;
    }
}
