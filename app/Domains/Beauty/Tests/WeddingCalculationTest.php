<?php declare(strict_types=1);

namespace App\Domains\Beauty\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingCalculationTest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function runExamples(): void
        {
            $service = new AppointmentCancellationService();

            // Пример 1: Свадебная группа 5 человек, отмена за 5 дней
            $app5 = new Appointment();
            $app5->is_wedding_group = true;
            $app5->group_size = 5;
            $app5->price_cents = 5000000; // 50 000 руб
            $app5->datetime_start = Carbon::now()->addDays(5);
            $app5->correlation_id = "test-wedding-5d";

            $res5 = $service->calculateRefund($app5, Carbon::now());

            echo "Example 1: Wedding Group (5 ppl) - 5 days before\n";
            echo "Base Price: 50,000 RUB\n";
            echo "Penalty: " . $res5["penalty_percent"] . "%\n";
            echo "Penalty Amount: " . ($res5["penalty_amount"] / 100) . " RUB\n\n";

            // Пример 2: Свадебная группа 5 человек, отмена за 30 часов
            $app30h = new Appointment();
            $app30h->is_wedding_group = true;
            $app30h->group_size = 5;
            $app30h->price_cents = 5000000; // 50 000 руб
            $app30h->datetime_start = Carbon::now()->addHours(30);
            $app30h->correlation_id = "test-wedding-30h";

            $res30h = $service->calculateRefund($app30h, Carbon::now());

            echo "Example 2: Wedding Group (5 ppl) - 30 hours before\n";
            echo "Base Price: 50,000 RUB\n";
            echo "Penalty: " . $res30h["penalty_percent"] . "%\n";
            echo "Penalty Amount: " . ($res30h["penalty_amount"] / 100) . " RUB\n";
        }
}
