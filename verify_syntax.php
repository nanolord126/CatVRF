<?php
$dirs = ['app/Domains', 'app/Http/Controllers', 'app/Services'];
$errors = 0;
$checked = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if ($f->getExtension() !== 'php') continue;
        $out = shell_exec('php -l ' . escapeshellarg($f->getPathname()) . ' 2>&1');
        $checked++;
        if (strpos($out, 'No syntax errors') === false) {
            echo $f->getPathname() . PHP_EOL . trim($out) . PHP_EOL . PHP_EOL;
            $errors++;
        }
    }
}
echo "Checked: {$checked} files" . PHP_EOL;
echo "Syntax errors: {$errors}" . PHP_EOL;
