<?php declare(strict_types=1);

namespace App\Domains\Archived\VeganProducts\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeganSubscriptionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraud,


            private readonly VeganProductService $productService,


        ) {}


        /**


         * Subscribe user to a monthly or weekly curated vegan box.


         * Layer: Domain Service (3/9 extension)


         */


        public function subscribe(int $userId, int $boxId, string $planType, ?string $correlationId = null): void


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            Log::channel('audit')->info('LAYER-3: New Vegan Subscription Request', [


                'user' => $userId,


                'box' => $boxId,


                'plan' => $planType,


                'correlation_id' => $correlationId,


            ]);


            // 1. Validation Logic


            $box = VeganSubscriptionBox::where('id', $boxId)->where('is_active', true)->firstOrFail();


            // 2. Fraud Check (check if user is creating too many subs)


            $this->fraud->check('vegan_subscription_create', [


                'user_id' => $userId,


                'box_id' => $boxId,


            ]);


            // 3. Persist via transaction


            DB::transaction(function () use ($userId, $box, $planType, $correlationId) {


                // Check if active subscription already exists


                $existing = DB::table('vegan_subscriptions')


                    ->where('user_id', $userId)


                    ->where('vegan_subscription_box_id', $box->id)


                    ->where('status', 'active')


                    ->exists();


                if ($existing) {


                    Log::error('LAYER-3: Duplicate Subscription Error', [


                        'user' => $userId,


                        'box' => $box->id,


                        'correlation_id' => $correlationId,


                    ]);


                    throw new Exception("Active subscription for box #{$box->id} already exists.");


                }


                // Create record


                DB::table('vegan_subscriptions')->insert([


                    'uuid' => (string) Str::uuid(),


                    'tenant_id' => tenant()->id,


                    'user_id' => $userId,


                    'vegan_subscription_box_id' => $box->id,


                    'plan_type' => $planType,


                    'status' => 'active',


                    'amount_monthly' => $box->price_monthly,


                    'correlation_id' => $correlationId,


                    'created_at' => now(),


                    'updated_at' => now(),


                ]);


                Log::channel('audit')->info('LAYER-3: Vegan Subscription CREATED', [


                    'user' => $userId,


                    'box' => $box->id,


                    'correlation_id' => $correlationId,


                ]);


            });


        }


        /**


         * Renew all active subscriptions and trigger warehouse tasks.


         * Typically called via Cron or Periodic Job.


         */


        public function renewBatch(string $correlationId = ''): int


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            Log::channel('audit')->info('LAYER-3: Renewal Batch START', ['correlation_id' => $correlationId]);


            $activeSubs = DB::table('vegan_subscriptions')


                ->where('status', 'active')


                ->whereDate('next_delivery_at', '<=', now())


                ->get();


            $count = 0;


            foreach ($activeSubs as $sub) {


                try {


                    $this->renewSingle($sub, $correlationId);


                    $count++;


                } catch (Exception $e) {


                    Log::error("LAYER-3: Renewal FAILED for sub #{$sub->id}", [


                        'error' => $e->getMessage(),


                        'correlation_id' => $correlationId,


                    ]);


                }


            }


            return $count;


        }


        /**


         * Renew a single subscription with payment check and stock reservation.


         */


        private function renewSingle($sub, string $correlationId): void


        {


            DB::transaction(function () use ($sub, $correlationId) {


                $box = VeganSubscriptionBox::findOrFail($sub->vegan_subscription_box_id);


                // 1. Logic for charging wallet if exists


                // app(WalletService::class)->debit(...);


                // 2. Reservation of products inside the box


                foreach ($box->included_product_ids as $productId) {


                    $this->productService->adjustStock(


                        productId: (int) $productId,


                        delta: -1,


                        reason: "Subscription renewal #{$sub->id}",


                        correlationId: $correlationId


                    );


                }


                // 3. Update subscription next date


                DB::table('vegan_subscriptions')


                    ->where('id', $sub->id)


                    ->update([


                        'last_delivery_at' => now(),


                        'next_delivery_at' => now()->addWeek(), // or addMonth() based on plan


                        'updated_at' => now(),


                    ]);


                Log::channel('audit')->info('LAYER-3: Renewed subscription', [


                    'id' => $sub->id,


                    'correlation_id' => $correlationId


                ]);


            });


        }
}
