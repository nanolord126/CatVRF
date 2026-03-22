#Requires -Version 5.0
# fix_fraud_v2.ps1 — Добавляет ->check() перед DB::transaction() в 70 сервисах
Set-Location 'c:\opt\kotvrf\CatVRF'

$fixedCheck = 0
$skipped    = 0
$filesFixed = @()

$fraudUse = 'use App\Services\FraudControlService;'

function Get-LineIndent([string]$text, [int]$pos) {
    $lineStart = $text.LastIndexOf("`n", $pos)
    if ($lineStart -lt 0) { $lineStart = 0 } else { $lineStart++ }
    $indent = ''
    for ($i = $lineStart; $i -lt $pos; $i++) {
        $ch = $text[$i]
        if ($ch -eq ' ' -or $ch -eq "`t") { $indent += $ch } else { break }
    }
    return $indent
}

function Build-CheckCall([string]$indent) {
    $i4 = $indent + '    '
    return "$indent`$this->fraudControlService->check(" + "`r`n" +
           "$i4" + "auth()->id() ?? 0," + "`r`n" +
           "$i4" + "__CLASS__ . '::' . __FUNCTION__," + "`r`n" +
           "$i4" + "0," + "`r`n" +
           "$i4" + "request()->ip()," + "`r`n" +
           "$i4" + "null," + "`r`n" +
           "$i4" + '`$correlationId ?? \Illuminate\Support\Str::uuid()->toString()' + "`r`n" +
           "$indent);" + "`r`n"
}

$allServices = Get-ChildItem -Path 'app\Domains' -Recurse -Filter '*.php' `
    | Where-Object { $_.FullName -match '\\Services\\' }

Write-Host "Processing $($allServices.Count) services..."

foreach ($file in $allServices) {
    $path    = $file.FullName
    $content = [System.IO.File]::ReadAllText($path)

    # Skip if no fraudControlService property or already has ->check(
    if (-not $content.Contains('fraudControlService')) { $skipped++; continue }
    if ($content.Contains('->check(')) { $skipped++; continue }

    $changed = $false

    # --- Try inserting before DB::transaction(
    $needle = 'DB::transaction('
    if ($content.Contains($needle)) {
        $pos = 0
        $insertCount = 0
        while ($true) {
            $txPos = $content.IndexOf($needle, $pos)
            if ($txPos -lt 0) { break }

            # Check last 300 chars for existing check to avoid duplicate
            $lookback = [Math]::Max(0, $txPos - 300)
            $preview  = $content.Substring($lookback, $txPos - $lookback)
            if ($preview.Contains('fraudControlService->check(')) {
                $pos = $txPos + $needle.Length; continue
            }

            $indent     = Get-LineIndent $content $txPos
            $checkCall  = Build-CheckCall $indent
            $content    = $content.Insert($txPos, $checkCall)
            $insertCount++
            $pos = $txPos + $checkCall.Length + $needle.Length
        }
        if ($insertCount -gt 0) {
            $changed = $true
            $fixedCheck += $insertCount
        }
    }

    # --- Fallback: insert before first Model::create( if no DB::transaction
    if (-not $changed) {
        $createPattern = [regex]'(?m)^([ \t]+)\w[\w\\]+::create\('
        $m = $createPattern.Match($content)
        if ($m.Success) {
            $indent    = $m.Groups[1].Value
            $checkCall = Build-CheckCall $indent
            $content   = $content.Insert($m.Index, $checkCall)
            $changed   = $true
            $fixedCheck++
        }
    }

    # --- Fallback 2: insert at first public function body start (after opening brace of first public method)
    if (-not $changed) {
        $methPattern = [regex]'(?m)^\s+(public function \w+[^{]+\{[ \t]*\r?\n)'
        $m = $methPattern.Match($content)
        if ($m.Success) {
            $insertAt  = $m.Index + $m.Length
            $indent    = '        '  # 8 spaces standard
            $checkCall = Build-CheckCall $indent
            $content   = $content.Insert($insertAt, $checkCall)
            $changed   = $true
            $fixedCheck++
        }
    }

    if ($changed) {
        [System.IO.File]::WriteAllText($path, $content, [System.Text.Encoding]::UTF8)
        $filesFixed += $file.Name
    }
}

Write-Host ''
Write-Host '======================================='
Write-Host '   fix_fraud_v2.ps1 — DONE'
Write-Host '======================================='
Write-Host "Added ->check() calls (total):  $fixedCheck"
Write-Host "Files modified:                  $($filesFixed.Count)"
Write-Host "Skipped (already OK / no FCS):  $skipped"
Write-Host ''
$filesFixed | ForEach-Object { Write-Host "  FIXED: $_" }
