Set-Location $PSScriptRoot

$files = Get-ChildItem -Path "app","tests" -Recurse -Filter "*.php" -ErrorAction SilentlyContinue

Write-Host "=== [1] TODO ==="
$todoCount = 0
foreach ($f in $files) {
    $c = [System.IO.File]::ReadAllText($f.FullName)
    if ($c -match 'TODO') {
        $lines = $c -split "`n"
        for ($i=0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match 'TODO') {
                Write-Host "$($f.Name):$($i+1): $($lines[$i].Trim())"
                $todoCount++
            }
        }
    }
}
Write-Host "Итого: $todoCount"

Write-Host ""
Write-Host "=== [2] return null ==="
$rnCount = 0
foreach ($f in $files) {
    $c = [System.IO.File]::ReadAllText($f.FullName)
    if ($c -match 'return null') {
        $lines = $c -split "`n"
        for ($i=0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match 'return null') {
                Write-Host "$($f.Name):$($i+1): $($lines[$i].Trim())"
                $rnCount++
            }
        }
    }
}
Write-Host "Итого: $rnCount"

Write-Host ""
Write-Host "=== [3] ::check( ==="
$checkCount = 0
foreach ($f in $files) {
    $c = [System.IO.File]::ReadAllText($f.FullName)
    if ($c -match '::check\(') {
        $lines = $c -split "`n"
        for ($i=0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match '::check\(') {
                Write-Host "$($f.Name):$($i+1): $($lines[$i].Trim())"
                $checkCount++
            }
        }
    }
}
Write-Host "Итого: $checkCount"

Write-Host ""
Write-Host "=== [4] id() — нестандартные (не auth, не uuid) ==="
$idCount = 0
foreach ($f in $files) {
    $c = [System.IO.File]::ReadAllText($f.FullName)
    if ($c -match 'id\(\)') {
        $lines = $c -split "`n"
        for ($i=0; $i -lt $lines.Count; $i++) {
            $line = $lines[$i]
            if ($line -match 'id\(\)' `
                -and $line -notmatch 'auth\(\)' `
                -and $line -notmatch 'uuid\(\)' `
                -and $line -notmatch 'Str::' `
                -and $line -notmatch 'methodId\(\)' `
                -and $line -notmatch 'queueId\(\)' `
                -and $line -notmatch 'getJobId\(\)' `
                -and $line -notmatch '//' `
                -and $line -notmatch '\*' `
            ) {
                Write-Host "$($f.Name):$($i+1): $($line.Trim())"
                $idCount++
            }
        }
    }
}
Write-Host "Итого нестандартных: $idCount"

Write-Host ""
Write-Host "=== [5] Facade static calls (not use/extends/comments) ==="
$facadeProb = 0
foreach ($f in $files) {
    if ($f.FullName -match '\\tests\\') { continue }
    $c = [System.IO.File]::ReadAllText($f.FullName)
    if ($c -match 'Facade') {
        $lines = $c -split "`n"
        for ($i=0; $i -lt $lines.Count; $i++) {
            $line = $lines[$i]
            if ($line -match 'Facade' -and $line -notmatch '^use ' -and $line -notmatch 'extends Facade' -and $line -notmatch '//' -and $line -notmatch '\*' -and $line -match '::[A-Z]') {
                Write-Host ("  " + $f.Name + ":" + ($i+1) + ": " + $line.Trim())
                $facadeProb++
            }
        }
    }
}
Write-Host ("Итого Facade static calls: " + $facadeProb)
