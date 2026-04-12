<?php declare(strict_types=1);

namespace App\Domains\Travel\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;


use Psr\Log\LoggerInterface;
final class DeductTransportationCommissionListener
{

    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function __construct(                    $itemType = $event instanceof FlightBooked ? 'flight' : 'transportation';

                    if ($item->agency === null) {
                        return;
                    }

                    $wallet = $item->agency->owner->wallet;

                    if ($wallet === null) {
                        throw new \RuntimeException('Agency owner wallet not found');
                    }

                    $wallet->lockForUpdate();

                    $commissionInCents = (int)($item->commission_amount * 100);

                    $wallet->decrement('current_balance', $commissionInCents);

                    BalanceTransaction::create([
                        'tenant_id' => $item->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionInCents,
                        'description' => "Commission for {$itemType} booking (#{$item->id})",
                        'reference_type' => "travel_{$itemType}",
                        'reference_id' => $item->id,
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info("Travel {$itemType} commission deducted", [
                        'item_id' => $item->id,
                        'item_type' => $itemType,
                        'agency_id' => $item->agency_id,
                        'commission_amount' => $item->commission_amount,
                        'correlation_id' => $event->correlationId,
                        'wallet_id' => $wallet->id,
                        'timestamp' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error("Travel {$itemType} commission deduction failed", [
                    'item_id' => $event->flight->id ?? $event->transportation->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
