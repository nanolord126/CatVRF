<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Services;


use Psr\Log\LoggerInterface;
final readonly class VendorManagementService
{

    private readonly string $correlationId;


    public function __construct(private FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {
            $this->correlationId = $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Регистрация нового вендора
         */
        public function registerVendor(array $data): WeddingVendor
        {
            $this->logger->info('VendorMgmt: Registering vendor', [
                'vendor_name' => $data['name'],
                'correlation_id' => $this->correlationId
            ]);

            return $this->db->transaction(function () use ($data) {
                $vendor = WeddingVendor::create([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'base_price' => $data['base_price'] ?? 0,
                    'currency' => $data['currency'] ?? 'RUB',
                    'portfolio_links' => $data['portfolio_links'] ?? [],
                    'equipment_list' => $data['equipment_list'] ?? [],
                    'is_verified' => false,
                    'rating' => 0,
                    'correlation_id' => $this->correlationId,
                    'tags' => $data['tags'] ?? [],
                ]);

                return $vendor;
            });
        }

        /**
         * Оценка вендора после свадьбы
         */
        public function addReview(WeddingVendor $vendor, int $userId, int $rating, string $comment): WeddingReview
        {
            $this->logger->info('VendorMgmt: Adding review', [
                'vendor_uuid' => $vendor->uuid,
                'rating' => $rating,
                'correlation_id' => $this->correlationId
            ]);

            return $this->db->transaction(function () use ($vendor, $userId, $rating, $comment) {
                $review = WeddingReview::create([
                    'user_id' => $userId,
                    'reviewable_type' => get_class($vendor),
                    'reviewable_id' => $vendor->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'is_published' => true,
                    'correlation_id' => $this->correlationId,
                ]);

                // Пересчет рейтинга вендора
                $avgRating = WeddingReview::where('reviewable_type', get_class($vendor))
                    ->where('reviewable_id', $vendor->id)
                    ->avg('rating');

                $vendor->update([
                    'rating' => (int) round($avgRating),
                    'correlation_id' => $this->correlationId,
                ]);

                return $review;
            });
        }

        /**
         * Верификация вендора (B2B)
         */
        public function verifyVendor(WeddingVendor $vendor, bool $isVerified = true): bool
        {
            $this->logger->info('VendorMgmt: Verifying vendor', [
                'vendor_uuid' => $vendor->uuid,
                'is_verified' => $isVerified,
                'correlation_id' => $this->correlationId
            ]);

            return $vendor->update([
                'is_verified' => $isVerified,
                'correlation_id' => $this->correlationId
            ]);
        }
}
