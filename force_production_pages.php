<?php

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources';
$fixedCount = 0;

function fixPages($filePath) {
    global $fixedCount;
    
    $content = file_get_contents($filePath);
    
    // Step 1: Remove any existing declare, namespace, use statements to rebuild from scratch
    $content = preg_replace('/<\?php.*?(?=\n(namespace|final|abstract|class))/s', '<?php', $content, 1);
    
    // Step 2: Extract relative path to build correct namespace
    $relativePath = str_replace(__DIR__ . '/', '', $filePath);
    preg_match('/app\/Filament\/Tenant\/Resources\/(.+?)\/Pages\/(.+?)\.php$/', $relativePath, $matches);
    
    if (empty($matches[1])) {
        echo "⚠️  Skip: $filePath (invalid path)\n";
        return;
    }
    
    $resourcePath = $matches[1];
    $pageName = $matches[2];
    $namespace = "App\\Filament\\Tenant\\Resources\\" . str_replace('/', '\\', $resourcePath) . "\\Pages";
    
    // Step 3: Extract class definition
    preg_match('/^(final\s+)?class\s+' . preg_quote($pageName) . '\s+extends\s+(\w+)/', $content, $classMatches);
    if (empty($classMatches[2])) {
        // Custom page with $view - extract parent class
        preg_match('/class\s+\w+\s+extends\s+(\w+)/', $content, $classMatches);
        if (empty($classMatches[1])) {
            $classMatches[1] = 'Page';
        }
    }
    
    $parentClass = $classMatches[2] ?? 'Page';
    
    // Step 4: Build correct file from scratch
    $newContent = "<?php\r\n\r\ndeclare(strict_types=1);\r\n\r\nnamespace $namespace;\r\n";
    
    // Add use statement based on parent class
    if (in_array($parentClass, ['ListRecords', 'CreateRecord', 'EditRecord', 'ViewRecord'])) {
        $newContent .= "\r\nuse Filament\Resources\Pages\\$parentClass;\r\n";
    } else {
        $newContent .= "\r\nuse Filament\Resources\Pages\Page;\r\n";
    }
    
    // Extract resource reference
    preg_match('/protected static string \$resource\s*=\s*(.+?);/s', $content, $resourceMatch);
    $resourceRef = $resourceMatch[1] ?? 'null';
    
    // Extract any custom properties or methods
    preg_match('/class\s+\w+.*?\{(.+?)\}/s', $content, $bodyMatch);
    $body = '';
    
    if (!empty($bodyMatch[1])) {
        $innerContent = $bodyMatch[1];
        
        // Extract properties
        if (preg_match_all('/protected\s+static\s+(?:string|int|bool|array|float)\s+\$\w+\s*=\s*[^;]+;/s', $innerContent, $propsMatch)) {
            foreach ($propsMatch[0] as $prop) {
                $body .= "    $prop\r\n";
            }
        }
        
        // Extract methods
        if (preg_match_all('/public\s+function\s+\w+\([^)]*\).*?\{.*?\}/s', $innerContent, $methodsMatch)) {
            foreach ($methodsMatch[0] as $method) {
                $body .= "    $method\r\n";
            }
        }
    }
    
    // Build class
    $newContent .= "\r\nfinal class $pageName extends $parentClass\r\n{\r\n";
    $newContent .= "    protected static string \$resource = $resourceRef;\r\n";
    
    if (!empty($body)) {
        $newContent .= "\r\n$body";
    }
    
    $newContent .= "}\r\n";
    
    // Step 5: Write with CRLF
    file_put_contents($filePath, $newContent);
    $fixedCount++;
    echo "✓ Fixed: $filePath\n";
}

function processDirectory($dir) {
    $files = glob($dir . '*/Pages/*.php');
    foreach ($files as $file) {
        fixPages($file);
    }
}

// Process all Pages
processDirectory($pagesDir . '/');

$subdirs = glob($pagesDir . '/*/', GLOB_ONLYDIR);
foreach ($subdirs as $subdir) {
    processDirectory($subdir);
    
    $nestedDirs = glob($subdir . '*/', GLOB_ONLYDIR);
    foreach ($nestedDirs as $nestedDir) {
        processDirectory($nestedDir);
    }
}

echo "\n✅ Fixed: $fixedCount files\n";
