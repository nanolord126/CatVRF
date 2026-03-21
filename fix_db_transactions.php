<?php

$files = [
    "app/Domains/Freelance/Services/FreelanceService.php",
    "app/Domains/HomeServices/Services/HomeServicesService.php", // updated
    "app/Domains/Logistics/Services/LogisticsService.php",
    "app/Domains/Photography/Services/PhotographyService.php",
    "app/Domains/Tickets/Services/TicketService.php"
];

foreach ($files as $f) {
    if (!file_exists($f)) { echo "Not found: $f\n"; continue; }
    $content = file_get_contents($f);
    if (strpos($content, 'DB::transaction') === false) {
        // Add DB facade
        if (strpos($content, 'use Illuminate\Support\Facades\DB;') === false) {
            $content = preg_replace('/(namespace App\\\\Domains\\\\[a-zA-Z]+\\\\Services;)/', "$1\n\nuse Illuminate\\Support\\Facades\\DB;", $content);
        }
        
        $addClass = <<<PHP

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable \$callback)
    {
        return DB::transaction(function () use (\$callback) {
            return \$callback();
        });
    }
}
PHP;
        $content = trim($content);
        if (strpos($content, "}\n}") !== false) {
            // strip multiple ending braces
        }
        $content = preg_replace('/}\s*$/', $addClass, $content);
        file_put_contents($f, $content);
        echo "Fixed DB::transaction in: $f\n";
    } else {
        echo "Already has DB: $f\n";
    }
}

