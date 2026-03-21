<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);
    
    // Add use Statement for FraudControlService if not exists
    if (strpos($content, 'FraudControlService') === false) {
        $content = preg_replace(
            "/(use Illuminate.*?;\n)/s",
            "$1use App\\Services\\Fraud\\FraudControlService;\n",
            $content,
            1
        );
    }
    
    // We can just dump a report of what methods are taking what arguments.
    // Actually regex replacement might be risky since each policy model has a different name.
    // e.g. view(User $user, Appointment $appointment)
}
echo "Checked " . count($files) . " files.\n";

