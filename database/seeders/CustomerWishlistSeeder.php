<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\CustomerWishlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Вишлисты клиентов (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class CustomerWishlistSeeder extends Seeder
{
    public function run(): void
    {
        CustomerWishlist::factory()
            ->count(10)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}            if (!$customer) {              return;          }            $wishlists = [              [                  'customer_account_id' => $customer->id,                  'item_type' => 'product',                  'item_name' => 'Профессиональный парикмахерский набор',                  'item_price' => 8500.00,                  'note' => 'Хочу приобрести на скидке',                  'wishlist_name' => 'Рабочие инструменты',                  'priority' => 'high',                  'desired_by_date' => now()->addMonths(1)->toDateString(),              ],              [                  'customer_account_id' => $customer->id,                  'item_type' => 'service',                  'item_name' => 'Персональная тренировка',                  'item_price' => 1500.00,                  'note' => 'Интересует утренний график',                  'wishlist_name' => 'Здоровье и фитнес',                  'priority' => 'medium',                  'desired_by_date' => now()->addMonths(2)->toDateString(),              ],          ];            foreach ($wishlists as $wishlist) {              CustomerWishlist::create([                  ...$wishlist,                  'correlation_id' => \Illuminate\Support\Str::uuid(),              ]);          }      }  }  
