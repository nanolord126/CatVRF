<?php
// Simple audit script
$projectRoot = dirname(__FILE__);
$resourcesPath = $projectRoot . '/app/Filament/Tenant/Resources';

function getAllPages($dir) {
    $result = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item[0] === '.') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            if ($item === 'Pages') {
                $files = scandir($path);
                foreach ($files as $f) {
                    if (str_ends_with($f, '.php')) {
                        $result[] = $path . '/' . $f;
                    }
                }
            } else {
                $result = array_merge($result, getAllPages($path));
            }
        }
    }
    return $result;
}

$pages = getAllPages($resourcesPath);
sort($pages);

$total = 0;
$valid = 0;
$broken = 0;
$errors = [];

foreach ($pages as $page) {
    $total++;
    $rel = str_replace($projectRoot . '/', '', $page);
    
    $content = file_get_contents($page);
    $hasError = false;
    $errorList = [];
    
    // Check for $resource property
    if (!preg_match('/protected\s+static\s+string\s+\$resource\s*=\s*(\w+)::class/', $content)) {
        $hasError = true;
        $errorList[] = 'Missing $resource property';
    }
    
    // Check namespace
    if (!preg_match('/namespace\s+([^;]+);/', $content, $m)) {
        $hasError = true;
        $errorList[] = 'No namespace found';
    }
    
    if ($hasError) {
        $broken++;
        $errors[] = ['file' => $rel, 'errors' => $errorList];
    } else {
        $valid++;
    }
}

echo "Total Pages: $total\n";
echo "Valid: $valid\n";
echo "Broken: $broken\n";
echo "\n";

if (!empty($errors)) {
    foreach ($errors as $e) {
        echo $e['file'] . "\n";
        foreach ($e['errors'] as $err) {
            echo "  - $err\n";
        }
    }
}
?>
