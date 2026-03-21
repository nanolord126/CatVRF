<?php
$directories = ['app/Services', 'app/Jobs', 'app/Listeners', 'app/Domains'];

function fix_strict_types($dir) {
    if (!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php' && (strpos($file->getFilename(), 'Service.php') !== false || strpos($file->getFilename(), 'Job.php') !== false || strpos($file->getFilename(), 'Listener.php') !== false)) {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'declare(strict_types=1);') === false) {
                $content = preg_replace('/<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $content);
                file_put_contents($file->getPathname(), $content);
                echo "Added strict_types to " . $file->getPathname() . "\n";
            }
        }
    }
}

foreach ($directories as $dir) {
    fix_strict_types($dir);
}
