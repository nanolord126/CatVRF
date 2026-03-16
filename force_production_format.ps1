$pagesPath = "c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources"
$fileCount = 0

Get-ChildItem -Path $pagesPath -Filter "*.php" -Recurse | Where-Object {$_.FullName -match "Pages"} | ForEach-Object {
    $filePath = $_.FullName
    $content = [System.IO.File]::ReadAllText($filePath, [System.Text.Encoding]::UTF8)
    
    # 1. Ensure declare(strict_types=1) after opening tag
    if ($content -notmatch '^\s*<\?php\s*\n\s*declare\(strict_types=1\)') {
        $content = $content -replace '^<\?php\s*\n', "<?php`r`n`r`ndeclare(strict_types=1);`r`n"
    }
    
    # 2. Convert to CRLF
    $content = $content -replace "`r`n", "`n"  # Normalize to LF first
    $content = $content -replace "`n", "`r`n"   # Then convert to CRLF
    
    # Write back UTF-8 no BOM
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($content)
    [System.IO.File]::WriteAllBytes($filePath, $bytes)
    
    $fileCount++
    if ($fileCount % 20 -eq 0) {
        Write-Host "Processed: $fileCount files"
    }
}

Write-Host "✅ Completed: $fileCount files converted to production format (UTF-8 no BOM + CRLF + declare)"
