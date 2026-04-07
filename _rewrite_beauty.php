<?php
declare(strict_types=1);

$dir = "app/Domains/Beauty";

// Delete old contents
if (is_dir($dir)) {
    exec("rm -rf " . escapeshellarg($dir) . "/*");
} else {
    mkdir($dir, 0777, true);
}

$structure = [
    "Models" => [],
    "DTOs" => [],
    "Domain/Services" => [],
    "Services/AI" => [],
    "Http/Requests" => [],
    "Http/Resources" => [],
    "Events" => [],
    "Listeners" => [],
    "Jobs" => [],
    "Filament/Resources" => [],
    "Filament/Pages" => []
];

foreach ($structure as $path => $files) {
    if (!is_dir("$dir/$path")) {
        mkdir("$dir/$path", 0777, true);
    }
}
echo "Beauty module cleaned and structured.\n";

