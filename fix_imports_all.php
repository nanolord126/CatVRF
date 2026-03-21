<?php
$domainPath = __DIR__ . "/app/Domains";
$verticals = array_diff(scandir($domainPath), array("..", "."));
foreach ($verticals as $vertical) {
    if (is_dir($domainPath . "/$vertical/Services")) {
        foreach (glob($domainPath . "/$vertical/Services/*.php") as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $imports = [];
            $newLines = [];
            $changed = false;
            foreach ($lines as $line) {
                if (strpos(trim($line), "use ") === 0 && strpos($line, ";") !== false && strpos($line, " class ") === false && strpos($line, " trait ") === false) {
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
}

