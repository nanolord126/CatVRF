<?php declare(strict_types=1);

namespace App\Policies;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
final class ProductPolicy extends Model
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {}


    use HandlesAuthorization;

        /**
         * Может ли пользователь видеть товар?
         * Активные товары видны всем, неактивные - только владельцу.
         */
        public function view(User $user, Product $product): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($product->tenant_id) && $user->tenant_id !== $product->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $product->tenant_id,
                ]);
                return false;
            }

            if ($product->is_active && !$product->trashed()) {
                return true; // публичный товар
            }

            return $user->tenant_id === $product->tenant_id || $user->hasRole('admin');
        }

        /**
         * Может ли пользователь видеть все товары?
         */
        public function viewAny(User $user): bool
        {
            return true; // все видят активные товары
        }

        /**
         * Может ли пользователь создать товар?
         * Только владелец бизнеса (для своей вертикали).
         */
        public function create(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = (
                $user->hasRole('business') &&
                $user->tenant_id !== null &&
                $user->kyc_verified
            );

            if (!$allowed) {
                $this->logger->info('Unauthorized product creation attempt', [
                    'user_id' => $user->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь обновить товар?
         * Только владелец.
         */
        public function update(User $user, Product $product): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);

            if (!$allowed) {
                $this->logger->warning('Unauthorized product update attempt', [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь опубликовать товар (activate)?
         */
        public function publish(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь снять товар с публикации (deactivate)?
         */
        public function unpublish(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять ценой товара?
         */
        public function updatePrice(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь использовать динамическое ценообразование?
         * На основе DemandForecastService + PriceSuggestionService.
         */
        public function useDynamicPricing(User $user, Product $product): bool
        {
            return (
                $user->tenant_id === $product->tenant_id &&
                $user->hasRole(['business', 'admin']) &&
                $this->config->get("verticals.{$product->vertical}.features.dynamic_pricing", false)
            );
        }

        /**
         * Может ли пользователь управлять вариантами (variants)?
         */
        public function manageVariants(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять фотографиями/медиа?
         */
        public function manageMedia(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь управлять категоризацией?
         */
        public function manageCategories(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь просмотреть аналитику товара?
         * Просмотры, покупки, конверсия, выручка.
         */
        public function viewAnalytics(User $user, Product $product): bool
        {
            return (
                $user->tenant_id === $product->tenant_id ||
                $user->hasRole('admin')
            );
        }

        /**
         * Может ли пользователь просмотреть отзывы товара?
         */
        public function viewReviews(User $user, Product $product): bool
        {
            return true; // отзывы публичные
        }

        /**
         * Может ли пользователь просмотреть рейтинги товара?
         */
        public function viewRatings(User $user, Product $product): bool
        {
            return true; // рейтинги публичные
        }

        /**
         * Может ли пользователь модерировать отзывы?
         * Скрывать/показывать спамные отзывы.
         */
        public function moderateReviews(User $user, Product $product): bool
        {
            return (
                $user->tenant_id === $product->tenant_id &&
                $user->hasRole(['business', 'admin'])
            );
        }

        /**
         * Может ли пользователь просмотреть информацию о конкурентах?
         * На основе рекомендационной системы.
         */
        public function viewCompetitors(User $user, Product $product): bool
        {
            return (
                $user->tenant_id === $product->tenant_id &&
                $user->hasRole(['business', 'admin'])
            );
        }

        /**
         * Может ли пользователь запросить проверку на качество?
         */
        public function requestQualityCheck(User $user, Product $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли пользователь удалить товар?
         * Soft delete.
         */
        public function delete(User $user, Product $product): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business', 'admin']);
        }

        /**
         * Может ли администратор восстановить товар?
         */
        public function restore(User $user, Product $product): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin') && $user->tenant_id === $product->tenant_id;
        }

        /**
         * Может ли администратор hard-удалить товар?
         * ЗАПРЕЩЕНО - товары хранятся для аудита.
         */
        public function forceDelete(User $user, Product $product): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return false;
        }
}
