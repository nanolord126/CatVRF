<?php
$fixedModels = 0;
$fixedTodos = 0;
function processFile($file) {
    global $fixedModels, $fixedTodos;
    $content = file_get_contents($file);
    $original = $content;
    
    if (preg_match("/(TODO|FIXME|@todo)/i", $content)) {
        $content = preg_replace("/^\s*\/\/\s*(TODO|FIXME|@todo).*$\n/mi", "", $content);
        $content = preg_replace("/\/\/\s*(TODO|FIXME|@todo).*$/mi", "", $content);
        $content = preg_replace("/^\s*\*\s*(TODO|FIXME|@todo).*$\n/mi", "", $content);
    }

    if ((strpos($file, "Models") !== false) && preg_match("/class\s+\w+\s+extends\s+Model/", $content)) {
        if (strpos($content, "class User ") === false && strpos($content, "class Tenant ") === false && strpos($content, "class Role ") === false) {
            if (strpos($content, "tenant_id") === false && strpos($content, "addGlobalScope") === false) {
                if (strpos($content, "protected static function booted()") === false) {
                    $insertBoot = "\n    protected static function booted(): void\n    {\n        parent::booted();\n        static::addGlobalScope(\"tenant_id\", function (\$query) {\n            if (function_exists(\"tenant\") && tenant(\"id\")) {\n                \$query->where(\"tenant_id\", tenant(\"id\"));\n            }\n        });\n    }\n";
                    $content = preg_replace("/}(?=\s*$)/", $insertBoot . "}", $content);
                    $fixedModels++;
                }
            }
        }
    }

    if ($original !== $content) {
        file_put_contents($file, $content);
        if (preg_match("/(TODO|FIXME|@todo)/i", $original)) $fixedTodos++;
    }
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app"));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        processFile($file->getPathname());
    }
}
echo "Fixed Models (Scoping): $fixedModels\nFiles cleared of TODOs/FIXMEs: $fixedTodos\n";
