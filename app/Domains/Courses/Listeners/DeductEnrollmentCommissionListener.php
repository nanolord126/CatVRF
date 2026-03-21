<?php declare(strict_types=1);

namespace App\Domains\Courses\Listeners;

use App\Domains\Courses\Events\EnrollmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeductEnrollmentCommissionListener implements ShouldQueue
{
    public function handle(EnrollmentCreated $event): void
    {
        try {
            Log::channel('audit')->info('Deducting enrollment commission', [
                'enrollment_id' => $event->enrollment->id,
                'correlation_id' => $event->correlationId,
                'amount' => $event->enrollment->commission_price,
            ]);

            DB::transaction(function () use ($event) {
                // Deduct 14% commission from instructor wallet/balance
                $instructorWallet = $event->enrollment->course->instructor_id
                    ? DB::table('wallets')->where('user_id', $event->enrollment->course->instructor_id)->first()
                    : null;

                if ($instructorWallet) {
                    DB::table('wallets')
                        ->where('id', $instructorWallet->id)
                        ->update(['balance' => DB::raw("balance - {$event->enrollment->commission_price}")]);
                }

                Log::channel('audit')->info('Enrollment commission deducted', [
                    'enrollment_id' => $event->enrollment->id,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to deduct enrollment commission', [
                'enrollment_id' => $event->enrollment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
