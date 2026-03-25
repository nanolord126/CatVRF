<?php

declare(strict_types=1);

/**
 * Симуляционный тест для расчета штрафов фотосессий (Канон 2026).
 */
function testPhotoSessionScale($type, $hoursBefore, $hasAiLook) {
    echo "--- Testing: Photo Session [{$type}] ---" . PHP_EOL;
    echo "Hours Before: {$hoursBefore}, AI Look: " . ($hasAiLook ? 'YES' : 'NO') . PHP_EOL;
    
    $daysBefore = floor($hoursBefore / 24);
    
    $basePenaltyPercent = match (true) {
        $daysBefore >= 14 => 0,
        $daysBefore >= 7  => 25,
        $daysBefore >= 3  => 50,
        $hoursBefore >= 48 => 75,
        default           => 100,
    };

    if ($hasAiLook && $basePenaltyPercent > 0 && $basePenaltyPercent < 100) {
        $basePenaltyPercent += 20;
        if ($basePenaltyPercent > 100) $basePenaltyPercent = 100;
    }

    $price = 200000; // 2000 руб
    $fee = ($price * ($basePenaltyPercent / 100));

    echo "Days Before: $daysBefore" . PHP_EOL;
    echo "Base Penalty %: $basePenaltyPercent" . PHP_EOL;
    echo "Final Fee: " . ($fee / 100) . " RUB (for 2000.00 RUB price)" . PHP_EOL;
    echo PHP_EOL;
}

// 1. Невеста: 10 дней до, без AI Look
// 7-14 дней -> 25%
testPhotoSessionScale("Невеста", 10 * 24, false);

// 2. Семейная: 36 часов до, с AI Look
// < 48 часов -> 100% (c AI Look + 20% но кап 100%)
testPhotoSessionScale("Семейная", 36, true);

// 3. Семейная: 50 часов до, с AI Look
// 48-72 часа -> 75% + 20% (AI Look) = 95%
testPhotoSessionScale("Семейная", 50, true);

// 4. Невеста: 20 дней до, с AI Look
// > 14 дней -> 0% (AI Look не добавляется к 0%)
testPhotoSessionScale("Невеста", 20 * 24, true);
