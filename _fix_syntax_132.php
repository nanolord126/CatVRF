<?php
/**
 * Mass fixer for 132 broken files in app/Domains
 * Patterns:
 * A) Bare use imports (use SomeClass; without namespace)
 * B) Duplicated wallet->credit() params with unclosed [
 * C) Double commas in constructors
 * D) Duplicate Carbon imports
 * E) Missing $fraud = assignment
 * F) Model->staticMethod → Model::staticMethod
 */

$files = file(__DIR__ . '/_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$fixed = 0;
$details = [];

foreach ($files as $file) {
    $file = trim($file);
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    $fileFixed = [];

    // === FIX A: Remove bare use imports (no namespace backslash) ===
    // These are lines like: use HasFactory; or use SomeTrait, AnotherTrait;
    // at the namespace level (before class declaration)
    $lines = explode("\n", $content);
    $classLineIdx = null;
    
    // Find where the class/trait/interface starts
    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*(final\s+)?(abstract\s+)?(readonly\s+)?(class|trait|interface|enum)\s+/', $line)) {
            $classLineIdx = $i;
            break;
        }
    }
    
    if ($classLineIdx !== null) {
        $removedLines = [];
        for ($i = 0; $i < $classLineIdx; $i++) {
            $trimmed = trim($lines[$i]);
            // Bare use: "use SomeClass;" or "use Trait1, Trait2;" (no backslash = no namespace)
            if (preg_match('/^use\s+[A-Z]\w+(\s*,\s*[A-Z]\w+)*\s*;$/', $trimmed)) {
                // Check it has NO backslash (= bare import, not namespaced)
                if (strpos($trimmed, '\\') === false) {
                    $removedLines[] = $i;
                    $lines[$i] = '';
                }
            }
        }
        if (count($removedLines) > 0) {
            $fileFixed[] = 'bare_use:' . count($removedLines);
        }
    }
    $content = implode("\n", $lines);
    
    // === FIX A2: Remove duplicate Carbon import ===
    // use Carbon\Carbon; + use Illuminate\Support\Carbon; → keep Carbon\Carbon only
    if (substr_count($content, 'use Carbon\\Carbon;') > 0 && substr_count($content, 'use Illuminate\\Support\\Carbon;') > 0) {
        $content = str_replace("use Illuminate\\Support\\Carbon;\n", '', $content);
        $fileFixed[] = 'dup_carbon';
    }
    
    // === FIX B: Duplicated wallet->credit() params ===
    // Pattern: ['correlation_id'=>$correlationId,\App\Domains\Wallet\Enums\BalanceTransactionType::WORD, $correlationId, null, null, ['
    // Fix: ['correlation_id'=>$correlationId,
    $pattern = "/\['correlation_id'=>\\\$correlationId,\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\\\$correlationId,\s*null,\s*null,\s*\[/";
    $count = 0;
    $content = preg_replace($pattern, "['correlation_id'=>\$correlationId,", $content, -1, $count);
    if ($count > 0) $fileFixed[] = "wallet_dup:$count";

    // === FIX B2: walletService->credit() with enum injected mid-array ===
    // Pattern: 'business_group_id' => $booking->business_group_id,\n    \App\Domains\Wallet\Enums\..., $correlationId, null, null, [\n    'booking_id'
    // Fix: remove the enum line
    $pattern2 = "/,\s*\n\s*\\\\App\\\\Domains\\\\Wallet\\\\Enums\\\\BalanceTransactionType::\w+,\s*\\\$correlationId,\s*null,\s*null,\s*\[\s*\n/";
    $count2 = 0;
    $content = preg_replace($pattern2, ",\n", $content, -1, $count2);
    if ($count2 > 0) $fileFixed[] = "wallet_multiline:$count2";

    // === FIX C: Double commas ===
    $count3 = 0;
    $content = preg_replace('/,,/', ',', $content, -1, $count3);
    if ($count3 > 0) $fileFixed[] = "double_comma:$count3";

    // === FIX D: Orphaned code before class (scopeExpired etc outside class) ===
    // If there's a method-like pattern BEFORE the class keyword, it's orphaned
    // Fix: wrap in the class
    // Check: does file have class declaration?
    if (preg_match('/^\s*(final\s+)?(abstract\s+)?(readonly\s+)?class\s+(\w+)/m', $content)) {
        // Check if there's a method outside class (e.g., public function before class line)
        $lines2 = explode("\n", $content);
        $classLine2 = null;
        $hasOrphaned = false;
        foreach ($lines2 as $idx => $l) {
            if (preg_match('/^\s*(final\s+)?(abstract\s+)?(readonly\s+)?class\s+/', $l)) {
                $classLine2 = $idx;
                break;
            }
            if (preg_match('/^\s*(public|protected|private)\s+function\s/', $l) || 
                preg_match('/^\s*\/\*\*\s*@param.*\$query\s*\*\//', $l)) {
                $hasOrphaned = true;
            }
        }
        
        if ($hasOrphaned && $classLine2 !== null) {
            // The Reservation model case: methods outside class
            // Find where namespace/use ends and orphaned code begins
            $lastImport = 0;
            for ($i = 0; $i < $classLine2; $i++) {
                $t = trim($lines2[$i]);
                if (preg_match('/^(namespace|use)\s/', $t) || $t === '' || strpos($t, '<?php') !== false || strpos($t, 'declare(') !== false) {
                    $lastImport = $i;
                }
            }
            // Move orphaned lines (between lastImport+1 and classLine2-1) to inside class
            $orphanedCode = [];
            for ($i = $lastImport + 1; $i < $classLine2; $i++) {
                $t = trim($lines2[$i]);
                if ($t !== '' && !preg_match('/^(namespace|use|declare|<\?php)/', $t)) {
                    $orphanedCode[] = '    ' . $lines2[$i]; // indent
                    $lines2[$i] = '';
                }
            }
            
            if (count($orphanedCode) > 0) {
                // Find the opening brace of the class
                $braceIdx = null;
                for ($i = $classLine2; $i < count($lines2); $i++) {
                    if (strpos($lines2[$i], '{') !== false) {
                        $braceIdx = $i;
                        break;
                    }
                }
                if ($braceIdx !== null) {
                    // Insert orphaned code right after the opening brace
                    array_splice($lines2, $braceIdx + 1, 0, $orphanedCode);
                    $content = implode("\n", $lines2);
                    $fileFixed[] = 'orphaned_code:' . count($orphanedCode);
                }
            }
        }
    }

    // === FIX E: Orphaned string $correlationId = ''; outside class ===
    $content = preg_replace('/^\s*string\s+\$correlationId\s*=\s*[\'"]{2}\s*;\s*$/m', '', $content);

    // === FIX F: Fix trait use inside class that refs non-imported traits ===
    // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels; inside class without imports
    // Add proper imports
    $traitImports = [
        'Dispatchable' => 'Illuminate\\Foundation\\Events\\Dispatchable',
        'InteractsWithSockets' => 'Illuminate\\Broadcasting\\InteractsWithSockets', 
        'SerializesModels' => 'Illuminate\\Queue\\SerializesModels',
        'InteractsWithQueue' => 'Illuminate\\Contracts\\Queue\\InteractsWithQueue',
        'Queueable' => 'Illuminate\\Bus\\Queueable',
        'SoftDeletes' => 'Illuminate\\Database\\Eloquent\\SoftDeletes',
    ];
    
    foreach ($traitImports as $short => $full) {
        // If trait is used inside class (use Dispatchable...) but NOT imported at namespace level
        if (preg_match('/\buse\s+.*\b' . $short . '\b/', $content) && 
            strpos($content, "use $full;") === false &&
            strpos($content, "use $full\n") === false) {
            // Check if the trait is used inside class body (after class keyword)
            if (preg_match('/class\s+\w+.*?\{[^}]*?\buse\b[^;]*\b' . $short . '\b/s', $content)) {
                // Add the import after last existing use statement
                $content = preg_replace(
                    '/(use [A-Z][\w\\\\]+;\s*\n)((?:\s*\n)*(?:\/\*|final|abstract|readonly|class|#\[))/',
                    "$1use $full;\n$2",
                    $content,
                    1
                );
                $fileFixed[] = "add_import:$short";
            }
        }
    }

    // === SAVE ===
    if ($content !== $original) {
        file_put_contents($file, $content);
        $fixed++;
        $details[] = basename($file) . ' → ' . implode(', ', $fileFixed);
        echo "FIXED: " . basename($file) . " [" . implode(', ', $fileFixed) . "]\n";
    }
}

echo "\n=== Total fixed: $fixed / " . count($files) . " ===\n";

// Verify
echo "\n--- Verifying syntax ---\n";
$stillBroken = 0;
$nowOk = 0;
foreach ($files as $file) {
    $file = trim($file);
    if (!file_exists($file)) continue;
    $out = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $stillBroken++;
        echo "STILL BROKEN: " . basename($file) . " → " . trim($out) . "\n";
    } else {
        $nowOk++;
    }
}
echo "\n=== RESULT: OK=$nowOk STILL_BROKEN=$stillBroken ===\n";
