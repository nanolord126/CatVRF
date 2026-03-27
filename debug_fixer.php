<?php
$file = "app/Domains/Photography/Jobs/UpdateSessionStatusJob.php";
$content = file_get_contents($file);
$idx = strpos($content, "__construct");
if ($idx !== false) {
    echo "Found __construct at $idx\n";
    echo substr($content, $idx, 300);
}

