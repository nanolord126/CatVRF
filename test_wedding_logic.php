<?php

declare(strict_types=1);

// Mock common classes due to environment issues
class Log { public static function channel($c) { return new class { public function info($m, $d) { echo "LOG($m): " . json_encode($d) . PHP_EOL; } }; } }
class Carbon { 
    public static function now() { return new self(); } 
    public static function parse($d) { return new self($d); }
    public function diffInDays($d, $s) { return 15; } // Mock: 15 days before
}
class DB { public static function transaction($f) { return $f(); } }
class FraudControlService { public static function check($d) { return true; } }
class Appointment { 
    public $id = 1234567; 
    public $price = 50000; // 500 руб в копейках
    public $is_wedding_event = true; 
    public $datetime_start = '2026-04-10 12:00:00';
    public $tags = ['ai_look' => true]; 
    public static function create($d) { return new self(); }
}

// Minimalistic simulation of WeddingEventService logic
// 30d: 0%, 14-30d: 20%, 7-14d: 45%, etc. + 15% AI Look.

function testWeddingScale($daysBefore, $hasAiLook) {
    echo "--- Testing: $daysBefore days before, AI Look: " . ($hasAiLook ? 'YES' : 'NO') . " ---" . PHP_EOL;
    
    $basePenaltyPercent = match (true) {
        $daysBefore >= 30 => 0,
        $daysBefore >= 14 => 20,
        $daysBefore >= 7  => 45,
        $daysBefore >= 3  => 70,
        default           => 100,
    };

    if ($hasAiLook && $basePenaltyPercent < 100) {
        $basePenaltyPercent += 15;
    }

    $price = 100000; // 1000 руб
    $fee = ($price * ($basePenaltyPercent / 100));

    echo "Base Penalty %: $basePenaltyPercent" . PHP_EOL;
    echo "Final Fee: " . $fee . " (for 1000.00 RUB price)" . PHP_EOL;
    echo PHP_EOL;
}

testWeddingScale(35, false); // 0%
testWeddingScale(20, false); // 20%
testWeddingScale(20, true);  // 20% + 15% = 35%
testWeddingScale(10, true);  // 45% + 15% = 60%
testWeddingScale(5, true);   // 70% + 15% = 85%
testWeddingScale(1, true);   // 100% (cap)
