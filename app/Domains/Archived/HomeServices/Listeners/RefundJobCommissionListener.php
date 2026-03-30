<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RefundJobCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;


        public function handle(ServiceJobCompleted $event): void


        {


            try {


                $job = $event->job;


                if ($job->status !== 'cancelled') {


                    return;


                }


                \DB::transaction(function () use ($job, $event) {


                    $wallet = \App\Models\Wallet::where('tenant_id', $job->tenant_id)->lockForUpdate()->firstOrFail();


                    $commissionAmount = (int)($job->commission_amount * 100);


                    $wallet->increment('balance', $commissionAmount);


                    \DB::table('balance_transactions')->insert([


                        'wallet_id' => $wallet->id,


                        'type' => 'refund',


                        'amount' => $commissionAmount,


                        'description' => "Service job commission refund #{$job->id}",


                        'correlation_id' => $event->correlationId,


                        'created_at' => now(),


                    ]);


                });


                \Log::channel('audit')->info('Job commission refunded', [


                    'job_id' => $job->id,


                    'commission_amount' => $job->commission_amount,


                    'correlation_id' => $event->correlationId,


                ]);


            } catch (\Throwable $e) {


                \Log::channel('audit')->error('Failed to refund job commission', [


                    'error' => $e->getMessage(),


                    'correlation_id' => $event->correlationId,


                ]);


                throw $e;


            }


        }
}
