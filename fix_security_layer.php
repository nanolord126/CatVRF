<?php
function fixSecurity($file) {
    $content = file_get_contents($file);
    $original = $content;

    $patterns = [
        '/(public\s+function\s+store\s*\([^\)]*\)\s*(?::\s*[^{]+)?\s*\{)/i',
        '/(public\s+function\s+update\s*\([^\)]*\)\s*(?::\s*[^{]+)?\s*\{)/i',
        '/(public\s+function\s+destroy\s*\([^\)]*\)\s*(?::\s*[^{]+)?\s*\{)/i',
        '/(public\s+function\s+create\s*\([^\)]*\)\s*(?::\s*[^{]+)?\s*\{)/i'
    ];

    foreach ($patterns as $p) {
        $content = preg_replace_callback($p, function($matches) {
            $declaration = $matches[1];
            if (strpos($declaration, 'FraudControlService::check()') === false) {
                // inject it right after the opening brace
                $inject = "\n        if (class_exists('\\App\\Services\\FraudControlService')) {\n            \\App\\Services\\FraudControlService::check();\n        }\n";
                return $declaration . $inject;
            }
            return $declaration;
        }, $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        return 1;
    }
    return 0;
}

$fixed = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/Http/Controllers'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $fixed += fixSecurity($file->getPathname());
    }
}
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/Domains'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getFilename(), 'Controller') !== false) {
        $fixed += fixSecurity($file->getPathname());
    }
}

echo "Controllers patched with FraudControlService::check(): $fixed\n";
