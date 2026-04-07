<?php
// Quick diagnostic: count parens and braces per line for GiftsService
$lines = file('app/Domains/PartySupplies/Gifts/Services/GiftsService.php');
foreach ($lines as $i => $line) {
    $ln = $i + 1;
    $op = substr_count($line, '(');
    $cp = substr_count($line, ')');
    $ob = substr_count($line, '{');
    $cb = substr_count($line, '}');
    if ($op != $cp || $ob != $cb) {
        echo "L$ln: parens($op/$cp) braces($ob/$cb) | " . trim(substr($line, 0, 80)) . "\n";
    }
}

// Also do full file paren/brace running count
echo "\n--- Running count ---\n";
$content = file_get_contents('app/Domains/PartySupplies/Gifts/Services/GiftsService.php');
$pd = 0; $bd = 0;
$inStr = false; $strChar = '';
for ($i = 0; $i < strlen($content); $i++) {
    $ch = $content[$i];
    
    // Simple string detection (not perfect but OK for this)
    if (!$inStr && ($ch === '"' || $ch === "'")) {
        $inStr = true;
        $strChar = $ch;
        continue;
    }
    if ($inStr && $ch === $strChar && ($i === 0 || $content[$i-1] !== '\\')) {
        $inStr = false;
        continue;
    }
    if ($inStr) continue;
    
    if ($ch === '(') $pd++;
    if ($ch === ')') $pd--;
    if ($ch === '{') $bd++;
    if ($ch === '}') $bd--;
    
    if ($pd < 0 || $bd < 0) {
        // Find line number
        $ln = substr_count(substr($content, 0, $i), "\n") + 1;
        echo "UNDERFLOW at char $i (L$ln): paren=$pd brace=$bd | context: ..." . substr($content, max(0,$i-20), 40) . "...\n";
    }
}
echo "FINAL: paren=$pd brace=$bd\n";
