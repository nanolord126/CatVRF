declare(strict_types=1);

<?php

namespace Modules\Beauty\Observers;

use Modules\Beauty\Models\BeautySalon;

/**
 * BeautySalonObserver
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BeautySalonObserver
{
    public function created(BeautySalon $salon): void
    {
        // Auto-create wallet account for the salon
        if (!$salon->wallet) {
            $salon->wallet()->createAccount([
                'name' => 'Main Account',
                'meta' => [
                    'type' => 'beauty_salon',
                    'salon_id' => $salon->id,
                    'tenant_id' => $salon->tenant_id,
                ],
            ]);

            \Log::info('Wallet account created for salon', [
                'salon_id' => $salon->id,
                'tenant_id' => $salon->tenant_id,
            ]);
        }
    }

    public function deleted(BeautySalon $salon): void
    {
        // Log deletion with audit trail
        \Log::warning('Beauty salon deleted', [
            'salon_id' => $salon->id,
            'name' => $salon->name,
            'tenant_id' => $salon->tenant_id,
        ]);
    }
}
