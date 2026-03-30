<?php declare(strict_types=1);

namespace Modules\Advertising\Database\Seeders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AdSeeder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function run(): void {
            $campaign = Campaign::updateOrCreate(['name' => 'Grand Opening'], [
                'tenant_id' => 'grand-hotel', 'budget' => 50000,
                'vertical' => 'hotel', 'is_active' => true,
                'start_date' => now(), 'end_date' => now()->addMonth(),
            ]);
    
            $campaign->creatives()->create([
                'title' => 'Stay Luxurious', 'content' => 'Premium Suites available',
                'link' => 'https://hotel.localhost/rooms', 'erid' => 'ERID-DEMO-2026',
            ]);
        }
}
