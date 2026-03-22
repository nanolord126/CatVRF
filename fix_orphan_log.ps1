$files = Get-ChildItem -Path "app" -Recurse -Filter "*.php"
$count = 0

# Pattern 1: fix wrong use import
$badUse = [regex]::Escape('use App\Services\Security\FraudControlService;')
$goodUse = 'use App\Services\FraudControlService;'

# Pattern 2: remaining Security::check calls (any variant)
$literal1 = [regex]::Escape('\App\Services\Security\FraudControlService::check(')
$p1 = "[ \t]*" + $literal1 + "[^;]*;\r?\n"

# Pattern 3: static check with $data or array first arg (any variant not already removed)
$literal4a = [regex]::Escape("FraudControlService::check(")
$p4 = "[ \t]*" + $literal4a + "\[?[^)]*\);\r?\n"

foreach ($f in $files) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $new = $content
    # Fix wrong use import
    if ($new -match [regex]::Escape('use App\Services\Security\FraudControlService;')) {
        $new = $new.Replace('use App\Services\Security\FraudControlService;', 'use App\Services\FraudControlService;')
    }
    # Remove remaining Security::check calls
    $new = [System.Text.RegularExpressions.Regex]::Replace($new, $p1, "")
    # Remove remaining static check calls with any argument pattern
    $new = [System.Text.RegularExpressions.Regex]::Replace($new, $p4, "")
    if ($new -ne $content) {
        [System.IO.File]::WriteAllText($f.FullName, $new, [System.Text.Encoding]::UTF8)
        $count++
        Write-Host "Fixed: $($f.Name)"
    }
}
Write-Host "Total fixed: $count"
