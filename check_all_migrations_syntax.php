<?php
// Проверка синтаксиса всех миграций PHP

$paths = [
    'C:\\opt\\kotvrf\\CatVRF\\database\\migrations\\tenant' => 'Tenant',
    'C:\\opt\\kotvrf\\CatVRF\\database\\migrations' => 'Root'
];

$errors = [];
$total = 0;
$ok = 0;

foreach ($paths as $path => $label) {
    echo "Checking $label migrations...\n";
    
    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $total++;
        $basename = basename($file);
        
        // Выполняем php -l
        $output = [];
        $return = 0;
        exec("php -l \"$file\" 2>&1", $output, $return);
        
        if ($return === 0) {
            $ok++;
            echo ".";
        } else {
            $errors[] = [
                'file' => $basename,
                'path' => $file,
                'error' => implode("\n", $output)
            ];
            echo "E";
        }
    }
    echo "\n";
}

echo "\n=== RESULTS ===\n";
echo "Total files: $total\n";
echo "OK: $ok\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n=== ERRORS ===\n";
    foreach ($errors as $err) {
        echo "\n" . $err['file'] . ":\n";
        echo $err['error'] . "\n";
    }
} else {
    echo "\nAll migrations have valid syntax!\n";
}
