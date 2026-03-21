<?php

$dirs = ['app/Domains', 'app/Services', 'app/Jobs'];
$violations = [];

$files = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $diter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($diter as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            if (strpos($file->getPathname(), 'Models') !== false || strpos($file->getPathname(), 'Pages') !== false) {
                continue;
            }
            $files[] = $file->getPathname();
        }
    }
}

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Skip testing/seeding stuff
    if (stripos($content, 'extends TestCase') !== false || stripos($content, 'Database\Seeder') !== false) {
        continue;
    }

    $hasMutation = preg_match('/->\s*(save|create|update|delete|insert|insertOrIgnore|updateOrInsert)\s*\(/i', $content) ||
                   preg_match('/(?:[A-Z][a-zA-Z0-9_]*)::(?:create|insert|update)\s*\(/', $content);

    $hasTransaction = stripos($content, 'DB::transaction') !== false;
    $hasAuditLog = preg_match("/Log::channel\('(audit|inventory|referral|promo|recommend|recommend_quality)'\)/", $content);
    
    // Specifically for Jobs
    $isJob = stripos($content, 'implements ShouldQueue') !== false || strpos($file, 'Jobs\\') !== false;

    if ($hasMutation && !$hasTransaction) {
        // Exclude specific cases where it might be wrapped upstream or safe
        if (strpos($file, 'Service') !== false) {
             $violations[$file][] = "Missing DB::transaction for mutation in Service";
        }
    }

    if ($hasMutation && !$hasAuditLog) {
         if (strpos($file, 'Service') !== false) {
             $violations[$file][] = "Missing Log::channel('audit'/'inventory'/'referral') for mutation";
         }
    }

    // Checking Jobs specifically
    if ($isJob) {
        if ($hasMutation && !$hasTransaction) {
            $violations[$file][] = "Missing DB::transaction in Job handling DB ops";
        }
        if (!$hasAuditLog) {
            $violations[$file][] = "Missing Log::channel('audit'/etc) in Job";
        }
    }
}

if (!empty($violations)) {
    echo "Found " . count($violations) . " files with CANON violations:\n";
    foreach ($violations as $file => $issues) {
        echo "- $file : " . implode(', ', $issues) . "\n";
    }
} else {
    echo "SUCCESS: Services and Jobs are mostly CANON 2026 compliant!\n";
}
