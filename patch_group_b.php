<?php
$domainPath = __DIR__ . "/app/Domains";
$groupB = ["Courses", "Entertainment", "Fitness", "HomeServices", "Hotels", "Logistics", "Medical", "Pet", "RealEstate", "Tickets", "Travel"];
foreach ($groupB as $vertical) {
    echo "Processing $vertical...\n";
    $servicePath = $domainPath . "/$vertical/Services";
    if (is_dir($servicePath)) {
        foreach (glob($servicePath . "/*.php") as $file) {
            $content = file_get_contents($file);
            $changed = false;

            // Add Fraud Control import if missing
            if (strpos($content, "FraudControlService") === false) {
                // Insert after namespace
                $content = preg_replace('/(namespace App\\\\Domains\\\\[a-zA-Z]+\\\\Services;)/', "$1\n\nuse App\\Services\\Security\\FraudControlService;\nuse Illuminate\\Support\\Facades\\Log;", $content);
                $changed = true;
            }

            // Inject FraudControlService::check into public methods that take arrays or perform actions
            $content = preg_replace_callback(
                '/public function ([a-zA-Z0-9_]+)\s*\(([^)]*)\)(?:\s*:\s*[a-zA-Z0-9_\\\\]+)?\s*\{/',
                function($matches) {
                    $methodName = $matches[1];
                    $params = $matches[2];

                    if ($methodName === '__construct') return $matches[0];

                    $correlationCode = '';
                    if (strpos($params, '$correlationId') === false) {
                        $correlationCode = '$correlationId = $correlationId ?? (string)\\Illuminate\\Support\\Str::uuid();';
                    } else {
                        $correlationCode = '';
                    }

                    $injection = "\n        // Canon 2026: Mandatory Fraud Check & Audit\n        " . $correlationCode . "\n        \\App\\Services\\Security\\FraudControlService::check(['method' => '{$methodName}'], \$correlationId ?? 'system');\n        \\Illuminate\\Support\\Facades\\Log::channel('audit')->info('CALL {$methodName}', ['domain' => __CLASS__]);\n";
                    return $matches[0] . $injection;
                },
                $content
            );

            // Fix double quotes around audit if existing
            $content = str_replace('Log::channel("audit")', "Log::channel('audit')", $content);

            if ($changed || strpos($content, "FraudControlService::check") !== false) {
                file_put_contents($file, $content);
                echo "Patched Service in $vertical: " . basename($file) . "\n";
            }
        }
    }
}
