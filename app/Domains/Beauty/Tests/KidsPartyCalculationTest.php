<?php

declare(strict_types=1);

/**
 * KidsPartyCalculationTest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/kidspartycalculationtest
 */


namespace App\Domains\Beauty\Tests;


use Carbon\Carbon;
use Tests\TestCase;

final class KidsPartyCalculationTest extends TestCase
{

    public function runExamples(): void
        {
            $service = new AppointmentCancellationService();

            // Пример 1: Группа из 6 детей, отмена за 5 дней
            $app5d = new Appointment();
            $app5d->is_kids_party = true;
            $app5d->kids_count = 6;
            $app5d->price_cents = 3000000; // 30 000 руб
            $app5d->datetime_start = Carbon::now()->addDays(5);
            $app5d->correlation_id = "test-kids-5d";

            $res5d = $service->calculateRefund($app5d, Carbon::now());

            echo "Example 1: Kids Party (6 kids) - 5 days before\n";
            echo "Base Price: 30,000 RUB\n";
            echo "Penalty %: " . $res5d["penalty_percent"] . "%\n"; // Base 15% * 1.25 multiplier = 18.75 -> 18%
            echo "Penalty Amount: " . ($res5d["penalty_amount"] / 100) . " RUB\n\n";

            // Пример 2: Группа из 6 детей, отмена за 30 часов
            $app30h = new Appointment();
            $app30h->is_kids_party = true;
            $app30h->kids_count = 6;
            $app30h->price_cents = 3000000; // 30 000 руб
            $app30h->datetime_start = Carbon::now()->addHours(30);
            $app30h->correlation_id = "test-kids-30h";

            $res30h = $service->calculateRefund($app30h, Carbon::now());

            echo "Example 2: Kids Party (6 kids) - 30 hours before\n";
            echo "Base Price: 30,000 RUB\n";
            echo "Penalty %: " . $res30h["penalty_percent"] . "%\n"; // Base 50% * 1.25 multiplier = 62.5 -> 62%
            echo "Penalty Amount: " . ($res30h["penalty_amount"] / 100) . " RUB\n";
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
