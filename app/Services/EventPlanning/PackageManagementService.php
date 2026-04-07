<?php declare(strict_types=1);

namespace App\Services\EventPlanning;

use App\Models\EventPlanning\EventPackage;
use Exception;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class PackageManagementService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}


    /**
         * Create a new corporate or private event package.
         */
        public function createPackage(
            string $name,
            string $description,
            int $fixedPrice,
            int $maxGuests,
            array $servicesIds = [],
            string $correlationId = null
        ): EventPackage {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Audit Log Start (Canon 2026: Logic trace)
            $this->logger->channel('audit')->info('[PackageService] Creating new package', [
                'correlation_id' => $correlationId,
                'name' => $name,
                'max_guests' => $maxGuests,
                'fixed_price_cents' => $fixedPrice,
            ]);

            try {
                // 2. Transaction Scope (Canon 2026: DB Mutation)
                $this->fraud->check(new \stdClass());
                return $this->db->transaction(function () use ($name, $description, $fixedPrice, $maxGuests, $servicesIds, $correlationId) {
                    // 3. Create core package
                    $package = EventPackage::create([
                        'uuid' => (string) Str::uuid(),
                        'correlation_id' => $correlationId,
                        'name' => $name,
                        'description' => $description,
                        'fixed_price' => $fixedPrice,
                        'max_guests' => $maxGuests,
                        'is_active' => true,
                        'includes_services' => $servicesIds, // JSONB cast in model
                        'tags' => ['corporate', 'b2b', 'bundle'],
                    ]);

                    // 4. Verification Step (Logical consistency)
                    if ($fixedPrice <= 0) {
                        throw new \InvalidArgumentException('[Validation] Package price must be positive.');
                    }

                    if ($maxGuests > 1000) {
                        $package->tags = array_merge($package->tags, ['mega-event', 'stadium']);
                        $package->save();
                    }

                    // 5. Success Log Audit
                    $this->logger->channel('audit')->info('[PackageService] Package successfully created', [
                        'correlation_id' => $correlationId,
                        'package_uuid' => $package->uuid,
                        'price' => $fixedPrice,
                        'guests' => $maxGuests,
                    ]);

                    return $package;
                });

            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                // 6. Error handling (Canon 2026: Logging of errors)
                $this->logger->channel('audit')->error('[PackageService] Package creation failed', [
                    'correlation_id' => $correlationId,
                    'name' => $name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Get all active packages for a specific event size.
         */
        public function getEligiblePackages(int $guestCount): Collection
        {
            // 7. Data Retrieval (Layer 4 Domain Query)
            return EventPackage::where('is_active', true)
                ->where('max_guests', '>=', $guestCount)
                ->orderBy('fixed_price', 'asc')
                ->limit(10)
                ->get();
        }
}
