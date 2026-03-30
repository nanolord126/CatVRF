<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(PropertySold $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    $listing = $event->listing;
                    $commission = (int) ($listing->sale_price * $listing->commission_percent / 100);
                    // WalletService::debit($listing->property->owner_id, $commission, 'commission');

                    Log::channel('audit')->info('Commission deducted', [
                        'property_id' => $listing->property_id,
                        'commission' => $commission,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to deduct commission', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
