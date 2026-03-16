<?php    declare(strict_types=1);


namespace Database\Seeders;
use App\Models\Tenants\CustomerReview;
use App\Models\Tenants\CustomerAccount;
use Illuminate\Database\Seeder;    final 

final class CustomerReviewSeeder extends Seeder  {      public function run(): void      {          $customer = CustomerAccount::first();            if (!$customer) {              return;          }            $reviews = [              [                  'customer_account_id' => $customer->id,                  'product_type' => 'product',                  'product_name' => 'Букет тюльпанов премиум',                  'rating' => 5,                  'review_text' => 'Отличный букет! Очень свежие цветы, красивое оформление. Всем рекомендую!',                  'status' => 'approved',                  'is_verified_purchase' => true,              ],              [                  'customer_account_id' => $customer->id,                  'product_type' => 'service',                  'product_name' => 'Доставка цветов',                  'rating' => 4,                  'review_text' => 'Быстрая доставка, цветы в идеальном состоянии',                  'status' => 'approved',                  'is_verified_purchase' => true,              ],          ];            foreach ($reviews as $review) {              CustomerReview::create([                  ...$review,                  'correlation_id' => \Illuminate\Support\Str::uuid(),              ]);          }      }  }  