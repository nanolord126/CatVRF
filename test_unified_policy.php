<?php

declare(strict_types=1);

/**
 * Симуляция Единой системы правил отмен 2026 (CatVRF).
 */
function testAppointmentPolicy($id, $type, $policy, $hoursBefore, $isAi = false, $price = 100000) {
    echo "--- Scenario #{$id}: {$type} / Policy: {$policy} ---" . PHP_EOL;
    echo "Time until event: {$hoursBefore} hours, AI Look: " . ($isAi ? 'YES' : 'NO') . PHP_EOL;

    $daysBefore = (int)floor($hoursBefore / 24);

    // Логика из AppointmentPolicyService:
    $basePenalty = match ($policy) {
        'standard' => match (true) {
            $hoursBefore >= 72 => 0,
            $hoursBefore >= 48 => 50,
            default           => 100,
        },
        'strict_14d' => match (true) {
            $daysBefore >= 14 => 0,
            $daysBefore >= 7  => 25,
            $daysBefore >= 3  => 50,
            $hoursBefore >= 48 => 75,
            default           => 100,
        },
        'strict_30d' => match (true) {
            $daysBefore >= 30 => 0,
            $daysBefore >= 14 => 25,
            $daysBefore >= 7  => 50,
            $hoursBefore >= 72 => 75,
            default           => 100,
        },
        default => 100
    };

    $aiMod = 0;
    if ($basePenalty > 0 && $basePenalty < 100 && $isAi) {
        $aiMod = in_array($type, ['wedding', 'corporate', 'photo_session']) ? 20 : 15;
    }

    $finalPercent = min(100, $basePenalty + $aiMod);
    $fee = ($price * ($finalPercent / 100));

    echo "Base Penalty %: $basePenalty" . PHP_EOL;
    echo "AI Modifier: +{$aiMod}%" . PHP_EOL;
    echo "Final Penalty %: $finalPercent" . PHP_EOL;
    echo "Cancellation Fee: " . ($fee / 100) . " RUB (from " . ($price / 100) . ")" . PHP_EOL;
    echo PHP_EOL;
}

// 1. Свадебная группа 6 человек за 5 дней (strict_30d)
// 5 дней < 7 дней -> 75% базовый
testAppointmentPolicy(1, "wedding", "strict_30d", 5 * 24, true);

// 2. Детский праздник 8 детей за 30 часов (strict_14d)
// 30 часов < 48 часов -> 100%
testAppointmentPolicy(2, "kids_party", "strict_14d", 30, false);

// 3. Корпоратив 12 человек за 10 дней (strict_30d)
// 10 дней (7-14д) -> 50% базов. + 20% AI
testAppointmentPolicy(3, "corporate", "strict_30d", 10 * 24, true);

// 4. Фотосессия за 36 часов (strict_14d)
// 36 часов < 48ч -> 100%
testAppointmentPolicy(4, "photo_session", "strict_14d", 36, true);

// 5. Одиночная услуга за 72 часа (standard)
// 72 часа -> 0% (бесплатная отмена у стандартных)
testAppointmentPolicy(5, "standard", "standard", 72, true);
