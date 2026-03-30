<?php declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductAppointmentConsumables extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
