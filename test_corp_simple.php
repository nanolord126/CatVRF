<?php declare(strict_types=1);

/**
 * ИМИТАЦИОННЫЙ ТЕСТ КОРПОРАТИВНОЙ ЛОГИКИ (БЕЗ ЯДРА LARAVEL)
 * Согласно КАНОНУ 2026 и заданию (8 и 15 человек, штрафы 10 дней).
 */

class MockCorporateEventService {
    /**
     * Шкала штрафов (10 дней)
     */
    public function calculateDailyPenalty(int $daysBefore): int {
        return match (true) {
            $daysBefore >= 10 => 0,   // Бесплатно
            $daysBefore >= 7  => 10,  // 7-9 дней: 10%
            $daysBefore >= 3  => 25,  // 3-6 дней: 25%
            $daysBefore >= 2  => 40,  // 48ч-72ч: 40%
            default           => 70,  // <48ч: 70%
        };
    }

    /**
     * Групповые множители
     */
    public function getGroupMultiplier(int $participants): float {
        return match (true) {
            $participants >= 15 => 1.35, // 15+ человек: 1.35x
            $participants >= 8  => 1.2,  // 8-14 человек: 1.2x
            default            => 1.0,
        };
    }
}

function runDemo() {
    $service = new MockCorporateEventService();
    $basePrice = 10000; // 10 000 руб

    echo "\033[1;32m--- РАСЧЁТ СТОИМОСТИ ДЛЯ ГРУПП (8 и 15 человек) ---\033[0m\n\n";

    foreach ([8, 15] as $pax) {
        $multiplier = $service->getGroupMultiplier($pax);
        $totalPrice = $basePrice * $multiplier;
        
        echo "👥 Группа: \033[1;33m$pax\033[0m человек\n";
        echo "💰 Базовая цена: " . number_format($basePrice, 0, '.', ' ') . " руб.\n";
        echo "📈 Множитель: \033[1;36m" . $multiplier . "x\033[0m\n";
        echo "✅ Итоговая цена: \033[1;32m" . number_format($totalPrice, 0, '.', ' ') . " руб.\033[0m\n";
        echo str_repeat("-", 45) . "\n";
    }

    echo "\n\033[1;35m--- ШКАЛА ШТРАФОВ (10-дневный период) ---\033[0m\n\n";
    $testDays = [
        12 => "Более 10 дней",
        10 => "Ровно 10 дней",
        8  => "В интервале 7-9 дней",
        7  => "Нижняя граница 10%",
        5  => "В интервале 3-6 дней",
        3  => "Нижняя граница 25%",
        2  => "В интервале 48-72ч",
        1  => "Менее 48 часов"
    ];

    foreach ($testDays as $days => $desc) {
        $penalty = $service->calculateDailyPenalty($days);
        $color = $penalty > 50 ? "\033[1;31m" : ($penalty > 0 ? "\033[1;33m" : "\033[1;32m");
        echo "📅 Срок: $days дн. ($desc) -> Штраф: $color$penalty%\033[0m\n";
    }
}

runDemo();
