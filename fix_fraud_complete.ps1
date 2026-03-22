#Requires -Version 5.0
# fix_fraud_complete.ps1 — Исправляет: broken injection, dupe use, добавляет ->check() вызовы
# UTF-8 / CRLF

$ErrorActionPreference = 'Stop'
Set-Location 'c:\opt\kotvrf\CatVRF'

$fixedBroken  = 0
$fixedDupe    = 0
$fixedCheck   = 0
$filesFixed   = @()

$fraudUse = 'use App\Services\FraudControlService;'
$fraudInject = 'FraudControlService $fraudControlService'

$checkBlock = @'

        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
'@

$allServices = Get-ChildItem -Path 'app\Domains' -Recurse -Filter '*.php' `
    | Where-Object { $_.FullName -match '\\Services\\' }

Write-Host "Total services to process: $($allServices.Count)"

foreach ($file in $allServices) {
    $path    = $file.FullName
    $content = [System.IO.File]::ReadAllText($path)
    $orig    = $content
    $dirty   = $false

    # ─── 1. Fix broken injection: FraudControlService \, → FraudControlService $fraudControlService,
    if ($content.Contains('FraudControlService \,')) {
        $content = $content.Replace('FraudControlService \,', "FraudControlService `$fraudControlService,")
        $fixedBroken++
        $dirty = $true
    }

    # ─── 2. Remove duplicate use App\Services\FraudControlService;
    # Handle adjacent duplicates (with or without blank line)
    $dupePattern = 'use App\\Services\\FraudControlService;\r?\n(\r?\n)?use App\\Services\\FraudControlService;\r?\n'
    if ($content -match $dupePattern) {
        # Keep first occurrence, remove second
        $firstPos = $content.IndexOf($fraudUse)
        if ($firstPos -ge 0) {
            $rest = $content.Substring($firstPos + $fraudUse.Length)
            $secondPos = $rest.IndexOf($fraudUse)
            if ($secondPos -ge 0) {
                # Remove second occurrence and its trailing newline
                $beforeSecond = $content.Substring(0, $firstPos + $fraudUse.Length + $secondPos)
                $afterSecond  = $rest.Substring($secondPos + $fraudUse.Length)
                $content = $beforeSecond + $afterSecond
                $fixedDupe++
                $dirty = $true
            }
        }
    }
    # Handle any remaining duplicates
    $safeUse = [regex]::Escape($fraudUse)
    $matches = [regex]::Matches($content, $safeUse)
    if ($matches.Count -gt 1) {
        # Keep first, remove all others
        $rebuilt = $content
        for ($i = $matches.Count - 1; $i -ge 1; $i--) {
            $m = $matches[$i]
            # Remove the line: find start of line before and after newline
            $start = $m.Index
            $end   = $start + $m.Length
            # skip trailing newline
            if ($end -lt $rebuilt.Length -and ($rebuilt[$end] -eq "`n" -or $rebuilt[$end] -eq "`r")) {
                if ($rebuilt[$end] -eq "`r" -and ($end+1) -lt $rebuilt.Length -and $rebuilt[$end+1] -eq "`n") { $end += 2 }
                else { $end++ }
            }
            # also skip leading blank line before if any
            $rebuilt = $rebuilt.Remove($start, $end - $start)
        }
        if ($rebuilt -ne $content) {
            $content = $rebuilt
            $fixedDupe++
            $dirty = $true
        }
    }

    # ─── 3. Add $this->fraudControlService->check() before DB::transaction(
    $hasFCS = $content.Contains('$this->fraudControlService') -or `
              $content.Contains($fraudInject)
    $hasCheck = $content.Contains('->check(')
    $hasTx    = $content.Contains('DB::transaction(')

    if ($hasFCS -and -not $hasCheck -and $hasTx) {
        # Insert check before EACH DB::transaction( occurrence
        # Capture indentation of the line containing DB::transaction(
        $txPattern = '(?m)^([ \t]*)(\$\w+ = )?DB::transaction\('
        $newContent = [regex]::Replace($content, $txPattern, {
            param($m)
            $indent   = $m.Groups[1].Value
            $varPart  = $m.Groups[2].Value
            $indent + $checkBlock.TrimStart("`r`n").Replace('        ', $indent) + "`r`n" + $indent + $varPart + 'DB::transaction('
        })
        if ($newContent -ne $content) {
            $content = $newContent
            $fixedCheck++
            $dirty = $true
        }
    }

    # ─── 4. Fallback: services with FCS but no DB::transaction and no ->check() — add before first ->create(
    $hasFCS2  = $content.Contains('$this->fraudControlService')
    $hasCheck2 = $content.Contains('->check(')
    if ($hasFCS2 -and -not $hasCheck2) {
        $createPattern = '(?m)^([ \t]*)\w+::create\('
        $found = [regex]::Match($content, $createPattern)
        if ($found.Success) {
            $indent = $found.Groups[1].Value
            $insertBefore = $found.Value
            $checkInsert  = $indent + $checkBlock.TrimStart("`r`n").Replace('        ', $indent) + "`r`n" + $insertBefore
            $content = $content.Replace($insertBefore, $checkInsert, 1)  # Replace only first
            $fixedCheck++
            $dirty = $true
        }
    }

    # ─── Save if changed
    if ($dirty) {
        [System.IO.File]::WriteAllText($path, $content, [System.Text.Encoding]::UTF8)
        $filesFixed += $file.Name
    }
}

Write-Host ''
Write-Host '======================================='
Write-Host '   fix_fraud_complete.ps1 — DONE'
Write-Host '======================================='
Write-Host "Fixed broken injections (FraudControlService \,):  $fixedBroken"
Write-Host "Fixed duplicate use imports:                        $fixedDupe"
Write-Host "Added ->check() calls:                             $fixedCheck"
Write-Host "Total files modified:                               $($filesFixed.Count)"
Write-Host ''
if ($filesFixed.Count -gt 0) {
    Write-Host 'Modified files:'
    $filesFixed | ForEach-Object { Write-Host "  $_" }
}
