<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupExpiredBookingsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private readonly string $correlationId,
        ) {}

        public function handle(): void
        {
            DB::transaction(function (): void {
                $expired = Appointment::query()
                    ->where('status', 'pending')
                    ->where('created_at', '<', now()->subMinutes(15))
                    ->update(['status' => 'expired']);

                Log::channel('audit')->info('Expired bookings cleaned', [
                    'count' => $expired,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }
}
