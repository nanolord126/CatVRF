<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VapeCatalogService
{

    /**
         * Конструктор с DP.
         */
        public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать новый бренд в вертикали Vapes.
         */
        public function createBrand(array $params, string $correlationId = null): VapeBrand
        {
            $correlationId ??= (string) Str::uuid();

            // 1. Fraud Check бренда
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vape_brand_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($params, $correlationId) {

                $brand = VapeBrand::create([
                    'name' => $params['name'],
                    'slug' => $params['slug'] ?? Str::slug($params['name']),
                    'country' => $params['country'] ?? null,
                    'logo_url' => $params['logo_url'] ?? null,
                    'metadata' => $params['metadata'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Vape brand created', [
                    'brand_id' => $brand->id,
                    'name' => $brand->name,
                    'correlation_id' => $correlationId,
                ]);

                return $brand;
            });
        }

        /**
         * Создать устройство (POD-система, Мод и т.д.)
         */
        public function createDevice(array $params, string $correlationId = null): VapeDevice
        {
            $correlationId ??= (string) Str::uuid();

            // 2. Fraud Check устройства
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vape_device_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($params, $correlationId) {

                $device = VapeDevice::create([
                    'vape_brand_id' => $params['vape_brand_id'],
                    'name' => $params['name'],
                    'type' => $params['type'] ?? 'pod',
                    'sku' => $params['sku'] ?? null,
                    'gtin' => $params['gtin'] ?? null,
                    'price_kopecks' => $params['price_kopecks'] ?? 0,
                    'battery_capacity_mah' => $params['battery_capacity_mah'] ?? null,
                    'max_power_watt' => $params['max_power_watt'] ?? null,
                    'metadata' => $params['metadata'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Vape device created', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'correlation_id' => $correlationId,
                ]);

                return $device;
            });
        }

        /**
         * Создать жидкость (Salt/Classic)
         */
        public function createLiquid(array $params, string $correlationId = null): VapeLiquid
        {
            $correlationId ??= (string) Str::uuid();

            // 3. Fraud Check жидкости
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vape_liquid_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($params, $correlationId) {

                $liquid = VapeLiquid::create([
                    'vape_brand_id' => $params['vape_brand_id'],
                    'name' => $params['name'],
                    'nicotine_strength_mg' => $params['nicotine_strength_mg'] ?? 20,
                    'nicotine_type' => $params['nicotine_type'] ?? 'salt',
                    'volume_ml' => $params['volume_ml'] ?? 30,
                    'gtin' => $params['gtin'] ?? null,
                    'price_kopecks' => $params['price_kopecks'] ?? 0,
                    'vg_pg_ratio' => $params['vg_pg_ratio'] ?? '50/50',
                    'flavor_profile' => $params['flavor_profile'] ?? [],
                    'metadata' => $params['metadata'] ?? [],
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Vape liquid created', [
                    'liquid_id' => $liquid->id,
                    'liquid_name' => $liquid->name,
                    'correlation_id' => $correlationId,
                ]);

                return $liquid;
            });
        }
}
