<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PropertyAutoCloseJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly ?RentalListing $listing = null,
            public readonly string $correlationId = '',
        ) {
            $this->onQueue('default');

        }

        public function retryUntil()
        {
            return now()->addHours(6);
        }

        public function handle(): void
        {
            try {
                // Если объявление было активным более 90 дней без просмотров, закрыть
                if ($this->listing->status === 'active' && $this->listing->created_at->addDays(90) < now()) {
                    $this->listing->update(['status' => 'archived']);

                    Log::channel('audit')->info('Property listing auto-closed', [
                        'listing_id' => $this->listing->id,
                        'reason' => 'Inactive for 90 days',
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Property auto-close job failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}
