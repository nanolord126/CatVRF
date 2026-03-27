<?php
declare(strict_types=1);

function recursiveFix($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isDir() || $file->getExtension() !== "php" || str_contains($file->getPathname(), "vendor")) continue;
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // ???????: public function __construct(?????????) { ... public function __construct() { ... } }
        $pattern = "/public function __construct\s*\((.*?)\)\s*\{.*?public function __construct\s*\(\)\s*\{.*?\}(\s*\})?/s";
        
        if (preg_match($pattern, $content)) {
            $new = preg_replace($pattern, 'public function __construct($1) {}', $content);
            if ($new !== $content) {
                file_put_contents($path, $new);
                echo "FIXED nested construct: $path\n";
            }
        }
    }
}

recursiveFix("C:\opt\kotvrf\CatVRF\app\Domains");
?>
