<?php
declare(strict_types=1);

$DOMAINS_PATH = __DIR__ . '/app/Domains';

echo "Removing empty folders...\n";

$folders = array_filter(
    scandir($DOMAINS_PATH),
    fn($item) => is_dir("$DOMAINS_PATH/$item") && !in_array($item, ['.', '..'])
);

$emptyFolders = [];
$deletedCount = 0;

foreach ($folders as $folder) {
    $folderPath = "$DOMAINS_PATH/$folder";
    
    if (isDirEmpty($folderPath)) {
        $emptyFolders[] = $folder;
        if (rmdir($folderPath)) {
            echo "Deleted: $folder\n";
            $deletedCount++;
        }
    }
}

echo "\n✅ Deleted $deletedCount empty folders\n";

// Final list
echo "\nFinal structure:\n";
$final = array_filter(
    scandir($DOMAINS_PATH),
    fn($item) => is_dir("$DOMAINS_PATH/$item") && !in_array($item, ['.', '..'])
);
sort($final);
foreach ($final as $folder) {
    echo "  • $folder\n";
}
echo "\nTotal folders: " . count($final) . "\n";

function isDirEmpty($dir) {
    $files = array_diff(scandir($dir), ['.', '..']);
    return empty($files);
}
?>
