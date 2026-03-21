<?php
$domainPath = __DIR__ . "/app/Domains";
$groupB = ["Courses", "Entertainment", "Fitness", "HomeServices", "Hotels", "Logistics", "Medical", "Pet", "RealEstate", "Tickets", "Travel"];
foreach ($groupB as $vertical) {
    foreach (glob($domainPath . "/$vertical/Services/*.php") as $file) {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $imports = [];
        $newLines = [];
        $changed = false;
        foreach ($lines as $line) {
            if (strpos(trim($line), "use ") === 0 && strpos($line, ";") !== false) {
                if (in_array(trim($line), $imports)) {
                    $changed = true;
                    continue; // skip duplicate
                }
                $imports[] = trim($line);
            }
            $newLines[] = $line;
        }
        if ($changed) {
            file_put_contents($file, implode("\n", $newLines));
            echo "Fixed imports in $file\n";
        }
    }
}

