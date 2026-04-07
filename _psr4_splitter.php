<?php
/**
 * PSR-4 Splitter: reads multi-class PHP files and splits them into individual files.
 * Each class gets its own file named after the class, in the same directory.
 * Original multi-class file is deleted after successful split.
 */

$files = [
    'app/Domains/BooksAndLiterature/Books/DTOs/BooksDtos.php',
    'app/Domains/BooksAndLiterature/Books/Models/BooksModels.php',
    'app/Domains/Education/Bloggers/Events/StreamEvents.php',
    'app/Domains/Education/Bloggers/Http/Middleware/SecurityMiddleware.php',
    'app/Domains/Education/Courses/Models/B2BModels.php',
    'app/Domains/Electronics/DTOs/ElectronicsDtos.php',
    'app/Domains/Electronics/Events/ElectronicsEvents.php',
    'app/Domains/Electronics/Models/ElectronicsStore.php',
    'app/Domains/Fashion/FashionRetail/Models/B2BModels.php',
    'app/Domains/Furniture/DTOs/FurnitureDtos.php',
    'app/Domains/Furniture/Models/FurnitureModels.php',
    'app/Domains/Gardening/DTOs/GardeningDtos.php',
    'app/Domains/GroceryAndDelivery/Events/OrderEvents.php',
    'app/Domains/GroceryAndDelivery/Integrations/ExternalIntegrations.php',
    'app/Domains/HobbyAndCraft/Hobby/DTOs/HobbyDtos.php',
    'app/Domains/HobbyAndCraft/Hobby/Models/HobbyModels.php',
    'app/Domains/HomeServices/Models/B2BModels.php',
    'app/Domains/Logistics/Models/B2BModels.php',
    'app/Domains/Luxury/Jewelry/DTOs/JewelryDtos.php',
    'app/Domains/Luxury/Jewelry/Models/JewelryModels.php',
    'app/Domains/Medical/MedicalHealthcare/Models/B2BModels.php',
    'app/Domains/ShortTermRentals/Policies/STRPolicies.php',
    'app/Domains/SportsNutrition/DTOs/SportsNutritionDtos.php',
    'app/Domains/ToysAndGames/Toys/DTOs/ToyDtos.php',
    'app/Domains/ToysAndGames/Toys/Jobs/ToyJobs.php',
    'app/Domains/ToysAndGames/Toys/Models/ToyModels.php',
    'app/Domains/VeganProducts/DTOs/VeganDtos.php',
    'app/Domains/VeganProducts/Events/VeganEvents.php',
    'app/Domains/VeganProducts/Jobs/VeganJobs.php',
    'app/Domains/VeganProducts/Models/VeganModels.php',
];

// Note: Skipping Art Filament Resources (ArtistResource, ArtworkResource, ProjectResource) 
// because Filament resource files legitimately contain inner classes (Pages, RelationManagers)

$totalCreated = 0;
$totalDeleted = 0;
$errors = [];

foreach ($files as $relPath) {
    $fullPath = __DIR__ . '/' . $relPath;
    if (!file_exists($fullPath)) {
        $errors[] = "NOT FOUND: $relPath";
        continue;
    }

    $content = file_get_contents($fullPath);
    $dir = dirname($fullPath);

    // Extract namespace
    if (!preg_match('/namespace\s+([\w\\\\]+)\s*;/', $content, $nsMatch)) {
        $errors[] = "NO NAMESPACE: $relPath";
        continue;
    }
    $namespace = $nsMatch[1];

    // Extract all use statements at the top (before first class)
    $useStatements = [];
    if (preg_match_all('/^use\s+[^;]+;/m', $content, $useMatches)) {
        $useStatements = $useMatches[0];
    }

    // Split by class boundaries
    // Find each class definition with everything before it (but after previous class end)
    $pattern = '/(?:^|\n)((?:\/\*\*[\s\S]*?\*\/\s*)?(?:final\s+)?(?:abstract\s+)?(?:readonly\s+)?class\s+(\w+)(?:\s+extends\s+[\w\\\\]+)?(?:\s+implements\s+[\w\\\\,\s]+)?\s*\{)/';
    
    if (!preg_match_all($pattern, $content, $classMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        $errors[] = "NO CLASSES FOUND: $relPath";
        continue;
    }

    $classCount = count($classMatches[2]);
    if ($classCount <= 1) {
        echo "SKIP (1 class): $relPath\n";
        continue;
    }

    // Extract each class body by finding balanced braces
    $classes = [];
    for ($i = 0; $i < $classCount; $i++) {
        $className = $classMatches[2][$i][0];
        $classStart = $classMatches[1][$i][1];
        
        // Find the opening brace of this class
        $bracePos = strpos($content, '{', $classStart);
        if ($bracePos === false) {
            $errors[] = "NO BRACE for class $className in $relPath";
            continue 2;
        }

        // Find matching closing brace
        $depth = 0;
        $pos = $bracePos;
        $len = strlen($content);
        while ($pos < $len) {
            if ($content[$pos] === '{') $depth++;
            elseif ($content[$pos] === '}') {
                $depth--;
                if ($depth === 0) break;
            }
            // Skip strings
            if ($content[$pos] === "'" || $content[$pos] === '"') {
                $quote = $content[$pos];
                $pos++;
                while ($pos < $len && $content[$pos] !== $quote) {
                    if ($content[$pos] === '\\') $pos++;
                    $pos++;
                }
            }
            $pos++;
        }
        $classEnd = $pos;

        // Get the full class text including docblock
        // Look backwards from classStart for docblock
        $docStart = $classStart;
        $before = substr($content, 0, $classStart);
        $before = rtrim($before);
        if (preg_match('/\/\*\*[\s\S]*?\*\/\s*$/', $before, $docMatch)) {
            $docStart = $classStart - strlen($docMatch[0]);
        }

        $classBody = substr($content, $docStart, $classEnd - $docStart + 1);

        // Find use statements specific to this class (between previous class end and this class start)
        $regionStart = ($i === 0) ? 0 : ($classes[$i-1]['end'] + 1);
        $region = substr($content, $regionStart, $docStart - $regionStart);
        $localUses = [];
        if (preg_match_all('/^use\s+[^;]+;/m', $region, $localUseMatches)) {
            $localUses = $localUseMatches[0];
        }

        $classes[] = [
            'name' => $className,
            'body' => $classBody,
            'start' => $docStart,
            'end' => $classEnd,
            'localUses' => $localUses,
        ];
    }

    // Build individual files
    $created = 0;
    foreach ($classes as $cls) {
        $fileName = $cls['name'] . '.php';
        $filePath = $dir . '/' . $fileName;

        // Merge use statements: global + local
        $allUses = array_unique(array_merge($useStatements, $cls['localUses']));
        sort($allUses);

        // Filter out use statements that reference classes defined in this same file
        $otherClassNames = array_map(fn($c) => $c['name'], $classes);
        $filteredUses = [];
        foreach ($allUses as $use) {
            $isInternal = false;
            foreach ($otherClassNames as $other) {
                if ($other === $cls['name']) continue;
                // Check if this use statement imports a class from same namespace
                if (preg_match('/use\s+' . preg_quote($namespace, '/') . '\\\\' . preg_quote($other, '/') . '\s*;/', $use)) {
                    $isInternal = true;
                    break;
                }
            }
            $filteredUses[] = $use; // Keep all - they might be needed
        }

        $fileContent = "<?php\n\ndeclare(strict_types=1);\n\nnamespace $namespace;\n\n";
        if (!empty($filteredUses)) {
            $fileContent .= implode("\n", $filteredUses) . "\n\n";
        }
        $fileContent .= trim($cls['body']) . "\n";

        file_put_contents($filePath, $fileContent);
        $created++;
        $totalCreated++;
    }

    // Verify all created files have valid syntax
    $allValid = true;
    foreach ($classes as $cls) {
        $filePath = $dir . '/' . $cls['name'] . '.php';
        $result = shell_exec("php -l " . escapeshellarg($filePath) . " 2>&1");
        if (strpos($result, 'Parse error') !== false || strpos($result, 'Fatal error') !== false) {
            $errors[] = "SYNTAX ERROR in split file: {$cls['name']}.php from $relPath: $result";
            $allValid = false;
        }
    }

    if ($allValid) {
        // Delete original multi-class file
        unlink($fullPath);
        $totalDeleted++;
        echo "SPLIT: $relPath -> $created files (original deleted)\n";
    } else {
        echo "SPLIT WITH ERRORS: $relPath -> $created files (original KEPT)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Created: $totalCreated files\n";
echo "Deleted originals: $totalDeleted\n";
echo "Errors: " . count($errors) . "\n";
foreach ($errors as $err) {
    echo "  - $err\n";
}
