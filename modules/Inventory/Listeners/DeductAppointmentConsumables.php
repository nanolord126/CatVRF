declare(strict_types=1);

<?php

namespace Modules\Inventory\Listeners;

use Modules\BeautyMasters\Models\Appointment;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * DeductAppointmentConsumables
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductAppointmentConsumables
{
    public function handle(Appointment $appointment): void
    {
        // Simple mapping: for any appointment, we deduct 50ml of specific consumables 
        // depending on the service_name. Production would have a proper mapping table.
        // For demonstration, deduct 'Shampoo' if 'Service' is 'Haircut'
        
        $consumablesMapping = [
            'Haircut' => ['sku' => 'SHAMPOO-01', 'qty' => 50],
            'Style' => ['sku' => 'GEL-01', 'qty' => 10],
        ];

        foreach ($consumablesMapping as $service => $data) {
            if (str_contains(strtolower($appointment->service_name), strtolower($service))) {
                $product = Product::where('sku', $data['sku'])->first();
                if ($product) {
                    DB::transaction(function () use ($product, $data, $appointment) {
                        $product->decrement('stock', $data['qty']);
                        
                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'out',
                            'quantity' => $data['qty'],
                            'reason' => 'Auto-consumption mapping for appointment #' . $appointment->id,
                            'correlation_id' => (string) Str::uuid(),
                            'user_id' => $appointment->master_id,
                        ]);
                    });
                }
            }
        }
    }
}
