# Fix PHP opening tag and declare spacing
$files = Get-ChildItem -Path "app" -Filter "*.php" -Recurse
$fixed = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $changed = $false
    
    # Fix <?php\n\n (blank line after opening tag)
    if ($content -match '^\<\?php\s*\n\s*\n') {
        $content = $content -replace '^\<\?php\s*\n\s*\n', "<?php`n"
        $changed = $true
    }
    
    # Fix declare...\n\n (blank line after declare)
    if ($content -match 'declare\(strict_types=1\)\s*\n\s*\n') {
        $content = $content -replace 'declare\(strict_types=1\)\s*\n\s*\n', "declare(strict_types=1);`n"
        $changed = $true
    }
    
    if ($changed) {
        Set-Content $file.FullName $content -Encoding UTF8 -NoNewline
        $fixed++
        Write-Host "Fixed: $($file.Name)"
    }
}
Write-Host "Total fixed: $fixed files"
