<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductEnrollmentCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
