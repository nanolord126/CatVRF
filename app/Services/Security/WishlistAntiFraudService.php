<?php declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WishlistAntiFraudService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Detect and prevent wishlist manipulation
         * Returns true if operation is safe, false if fraudulent
         */
        public function checkWishlistPayment(int $userId, array $items): bool
        {
            $correlationId = request()->header('X-Correlation-ID') ?? (string)\Illuminate\Support\Str::uuid()->toString();

            // Check 1: Unusual time pattern
            if ($this->isUnusualTimePattern($userId)) {
                Log::channel('fraud_alert')->warning('Wishlist fraud: unusual time pattern', [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            // Check 2: Rapid add-to-cart and pay
            if ($this->isRapidAddAndPay($userId)) {
                Log::channel('fraud_alert')->warning('Wishlist fraud: rapid add and pay', [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            // Check 3: Item price manipulation
            if ($this->isPriceManipulation($items)) {
                Log::channel('fraud_alert')->warning('Wishlist fraud: price manipulation', [
                    'items' => count($items),
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            // Check 4: High-value items from unknown sellers
            if ($this->isHighValueFromUnknownSellers($items, $userId)) {
                Log::channel('fraud_alert')->warning('Wishlist fraud: high value from unknown sellers', [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            // Check 5: Bulk wishlist payment (>50 items at once)
            if (count($items) > 50) {
                Log::channel('fraud_alert')->warning('Wishlist fraud: bulk payment attempt', [
                    'user_id' => $userId,
                    'item_count' => count($items),
                    'correlation_id' => $correlationId,
                ]);
                return false;
            }

            return true;
        }

        private function isUnusualTimePattern(int $userId): bool
        {
            // User typically shops 9-20h, but now shopping at 3am
            $hour = now()->hour;

            if ($hour >= 0 && $hour <= 6) {
                // Check if user ever shops at night
                $nightPurchases = DB::table('orders')
                    ->where('user_id', $userId)
                    ->whereRaw('HOUR(created_at) BETWEEN 0 AND 6')
                    ->count();

                return $nightPurchases === 0;
            }

            return false;
        }

        private function isRapidAddAndPay(int $userId): bool
        {
            // Get wishlist items added in last 5 minutes
            $recentWishlist = DB::table('wishlist_items')
                ->where('user_id', $userId)
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            // If >30 items added in 5 minutes, suspicious
            if ($recentWishlist > 30) {
                return true;
            }

            // Check if user is trying to buy those items immediately
            $recentOrders = DB::table('orders')
                ->where('user_id', $userId)
                ->where('created_at', '>', now()->subMinutes(10))
                ->count();

            return $recentWishlist > 0 && $recentOrders > 0;
        }

        private function isPriceManipulation(array $items): bool
        {
            foreach ($items as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'] ?? null)
                    ->first();

                if (!$product) {
                    continue;
                }

                // If price in order is <50% of actual price, suspicious
                if (($item['price'] ?? 0) < ($product->price * 0.5)) {
                    return true;
                }
            }

            return false;
        }

        private function isHighValueFromUnknownSellers(array $items, int $userId): bool
        {
            $totalValue = 0;
            $unknownSellers = 0;

            foreach ($items as $item) {
                $totalValue += $item['price'] ?? 0;

                // Check if user has purchased from this seller before
                $sellerId = $item['seller_id'] ?? null;
                $previousPurchases = DB::table('orders')
                    ->where('user_id', $userId)
                    ->where('seller_id', $sellerId)
                    ->count();

                if ($previousPurchases === 0) {
                    $unknownSellers++;
                }
            }

            // If >5000 rubles from unknown sellers, suspicious
            return $totalValue > 500000 && $unknownSellers > 3;
        }
}
