<?php
declare(strict_types=1);

/**
 * PSR-4 Splitter v2: splits multi-class PHP files into individual files.
 * Approach: use token_get_all() for reliable PHP parsing.
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
    $tokens = token_get_all($content);

    // Extract namespace
    $namespace = '';
    for ($i = 0; $i < count($tokens); $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
            $ns = '';
            $i++;
            while (isset($tokens[$i]) && $tokens[$i] !== ';') {
                if (is_array($tokens[$i])) {
                    $ns .= $tokens[$i][1];
                } else {
                    $ns .= $tokens[$i];
                }
                $i++;
            }
            $namespace = trim($ns);
            break;
        }
    }

    if (empty($namespace)) {
        $errors[] = "NO NAMESPACE: $relPath";
        continue;
    }

    // Collect all use statements
    $useStatements = [];
    for ($i = 0; $i < count($tokens); $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_USE) {
            // Check this is a top-level use (not trait use inside class)
            // We'll collect the line
            $line = $tokens[$i][2];
            $stmt = '';
            while (isset($tokens[$i]) && $tokens[$i] !== ';') {
                if (is_array($tokens[$i])) {
                    $stmt .= $tokens[$i][1];
                } else {
                    $stmt .= $tokens[$i];
                }
                $i++;
            }
            $stmt .= ';';
            $useStatements[] = $stmt;
        }
    }

    // Find class boundaries using brace counting
    // Strategy: find each top-level class keyword, then track braces
    $classInfos = [];
    $depth = 0;
    $inClass = false;
    $currentClassName = '';
    $currentClassStart = 0;
    $classDepth = 0;
    $docComment = '';
    $lastDocComment = '';

    for ($i = 0; $i < count($tokens); $i++) {
        $tok = $tokens[$i];

        // Track doc comments
        if (is_array($tok) && $tok[0] === T_DOC_COMMENT) {
            $lastDocComment = $tok[1];
        }

        if (is_array($tok) && $tok[0] === T_CLASS && !$inClass) {
            // Check it's not ::class
            // Look back for ::
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && !is_array($tokens[$prev]) && $tokens[$prev] === ':') {
                continue; // ::class
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_DOUBLE_COLON) {
                continue; // ::class
            }

            $inClass = true;
            $classDepth = $depth;
            $currentClassStart = is_array($tok) ? $tok[2] : 0;
            $docComment = $lastDocComment;
            $lastDocComment = '';

            // Get class name
            $j = $i + 1;
            while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            $currentClassName = is_array($tokens[$j]) ? $tokens[$j][1] : '';

            // Collect the full class declaration line and modifiers
            // Look backwards for final, abstract, readonly
            $modifiers = '';
            $k = $prev;
            while ($k >= 0) {
                if (is_array($tokens[$k])) {
                    if (in_array($tokens[$k][0], [T_FINAL, T_ABSTRACT, T_READONLY])) {
                        $modifiers = $tokens[$k][1] . ' ' . $modifiers;
                        $k--;
                        // skip whitespace
                        while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                            $k--;
                        }
                        continue;
                    }
                }
                break;
            }

            // Now collect everything from class keyword to opening brace
            $classHeader = trim($modifiers) . ' ';
            $m = $i;
            while (isset($tokens[$m]) && $tokens[$m] !== '{') {
                if (is_array($tokens[$m])) {
                    $classHeader .= $tokens[$m][1];
                } else {
                    $classHeader .= $tokens[$m];
                }
                $m++;
            }
            $classHeader = trim($classHeader);

            // Now track braces to find end of class
            // Skip to opening brace
            while (isset($tokens[$i]) && $tokens[$i] !== '{') {
                $i++;
            }
            // $tokens[$i] is now '{'
            $braceCount = 1;
            $bodyTokens = ['{'];
            $i++;
            while (isset($tokens[$i]) && $braceCount > 0) {
                if (!is_array($tokens[$i])) {
                    if ($tokens[$i] === '{') $braceCount++;
                    elseif ($tokens[$i] === '}') $braceCount--;
                    $bodyTokens[] = $tokens[$i];
                } else {
                    $bodyTokens[] = $tokens[$i][1];
                }
                if ($braceCount > 0) $i++;
            }

            $bodyStr = implode('', $bodyTokens);

            $classInfos[] = [
                'name' => $currentClassName,
                'header' => $classHeader,
                'body' => $bodyStr,
                'docComment' => $docComment,
            ];

            $inClass = false;
            $docComment = '';
        }
    }

    if (count($classInfos) <= 1) {
        echo "SKIP (<=1 class): $relPath\n";
        continue;
    }

    // Filter use statements: only top-level (before first class)
    // Find the line number of first class to separate top-level uses from trait uses
    $topLevelUses = [];
    foreach ($useStatements as $use) {
        // Trait uses contain class names without full paths, or are inside class bodies
        // Simple heuristic: top-level uses start with 'use ' followed by a namespace-like path
        if (preg_match('/^use\s+[A-Z]/', $use) || preg_match('/^use\s+Illuminate\\\\/', $use) || preg_match('/^use\s+App\\\\/', $use)) {
            $topLevelUses[] = $use;
        }
    }
    // Deduplicate
    $topLevelUses = array_unique($topLevelUses);
    sort($topLevelUses);

    // Generate individual files
    $created = 0;
    foreach ($classInfos as $cls) {
        $fileName = $cls['name'] . '.php';
        $filePath = $dir . '/' . $fileName;

        // Determine which use statements this class needs
        // For simplicity, include all top-level use statements
        $fileContent = "<?php\n\ndeclare(strict_types=1);\n\nnamespace $namespace;\n\n";
        if (!empty($topLevelUses)) {
            $fileContent .= implode("\n", $topLevelUses) . "\n\n";
        }
        if (!empty($cls['docComment'])) {
            $fileContent .= $cls['docComment'] . "\n";
        }
        $fileContent .= $cls['header'] . "\n" . $cls['body'] . "\n";

        file_put_contents($filePath, $fileContent);
        $created++;
        $totalCreated++;
    }

    // Verify syntax of all created files
    $allValid = true;
    foreach ($classInfos as $cls) {
        $filePath = $dir . '/' . $cls['name'] . '.php';
        $result = shell_exec("php -l " . escapeshellarg($filePath) . " 2>&1");
        if (strpos($result, 'Parse error') !== false || strpos($result, 'Fatal error') !== false) {
            $errors[] = "SYNTAX ERR: {$cls['name']}.php <- $relPath : " . trim($result);
            $allValid = false;
        }
    }

    if ($allValid) {
        unlink($fullPath);
        $totalDeleted++;
        echo "OK SPLIT: $relPath -> $created files [deleted original]\n";
    } else {
        echo "!! SPLIT ERRORS: $relPath -> $created files [kept original]\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files created: $totalCreated\n";
echo "Originals deleted: $totalDeleted\n";
echo "Errors: " . count($errors) . "\n";
foreach ($errors as $err) {
    echo "  ERR: $err\n";
}
