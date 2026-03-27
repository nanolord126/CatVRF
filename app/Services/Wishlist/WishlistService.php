<?php declare(strict_types=1);

namespace App\Services\Wishlist;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WishlistService
{
    /**
     * Add item to wishlist
     */
    public function addItem(int $userId, string $itemType, int $itemId, array $metadata = []): array
    {
        $correlationId = Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($userId, $itemType, $itemId, $metadata, $correlationId) {
                // Check if item already in wishlist
                $existing = DB::table('wishlist_items')
                    ->where('user_id', $userId)
                    ->where('item_type', $itemType)
                    ->where('item_id', $itemId)
                    ->first();

                if ($existing) {
                    return [
                        'success' => false,
                        'message' => 'Item already in wishlist',
                        'correlation_id' => $correlationId,
                    ];
                }

                // Add to wishlist
                DB::table('wishlist_items')->insert([
                    'user_id' => $userId,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'metadata' => json_encode($metadata),
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Clear user cache
                $this->invalidateUserCache($userId);

                Log::channel('audit')->info('Wishlist: item added', [
                    'user_id' => $userId,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Added to wishlist',
                    'correlation_id' => $correlationId,
                ];
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Wishlist: add error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Remove item from wishlist
     */
    public function removeItem(int $userId, string $itemType, int $itemId): bool
    {
        $correlationId = Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($userId, $itemType, $itemId, $correlationId) {
                $deleted = DB::table('wishlist_items')
                    ->where('user_id', $userId)
                    ->where('item_type', $itemType)
                    ->where('item_id', $itemId)
                    ->delete();

                if ($deleted) {
                    $this->invalidateUserCache($userId);

                    Log::channel('audit')->info('Wishlist: item removed', [
                        'user_id' => $userId,
                        'item_type' => $itemType,
                        'item_id' => $itemId,
                        'correlation_id' => $correlationId,
                    ]);
                }

                return (bool) $deleted;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Wishlist: remove error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Get user's wishlist (alias for getUserWishlist)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWishlist(int $userId): array
    {
        return DB::table('wishlist_items')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Get wishlist count for a product
     */
    public function getProductWishlistCount(string $itemType, int $itemId): int
    {
        return (int) DB::table('wishlist_items')
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->count();
    }

    /**
     * Get user's wishlist (full, with optional filter)
     */
    public function getUserWishlist(int $userId, ?string $itemType = null): Collection
    {
        $query = DB::table('wishlist_items')
            ->where('user_id', $userId);

        if ($itemType) {
            $query->where('item_type', $itemType);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check if item in user's wishlist
     */
    public function hasItem(int $userId, string $itemType, int $itemId): bool
    {
        return DB::table('wishlist_items')
            ->where('user_id', $userId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->exists();
    }

    /**
     * Get wishlist count
     */
    public function getWishlistCount(int $userId, ?string $itemType = null): int
    {
        $query = DB::table('wishlist_items')
            ->where('user_id', $userId);

        if ($itemType) {
            $query->where('item_type', $itemType);
        }

        return $query->count();
    }

    /**
     * Share wishlist (create shared link)
     */
    public function shareWishlist(int $userId, ?string $itemType = null): string
    {
        $shareToken = Str::random(32);
        $correlationId = Str::uuid()->toString();

        DB::table('wishlist_shares')->insert([
            'user_id' => $userId,
            'item_type' => $itemType,
            'share_token' => $shareToken,
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::channel('audit')->info('Wishlist: shared', [
            'user_id' => $userId,
            'item_type' => $itemType,
            'share_token' => $shareToken,
            'correlation_id' => $correlationId,
        ]);

        return route('wishlist.shared', ['token' => $shareToken]);
    }

    /**
     * Get shared wishlist
     */
    public function getSharedWishlist(string $shareToken): Collection
    {
        $share = DB::table('wishlist_shares')
            ->where('share_token', $shareToken)
            ->first();

        if (!$share) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Shared wishlist not found for token: ' . $shareToken
            );
        }

        $query = DB::table('wishlist_items')
            ->where('user_id', $share->user_id);

        if ($share->item_type) {
            $query->where('item_type', $share->item_type);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Clear user's wishlist cache
     */
    private function invalidateUserCache(int $userId): void
    {
        \Illuminate\Support\Facades\Cache::forget("wishlist:user:$userId");
    }
}
