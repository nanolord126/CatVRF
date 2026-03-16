# Convert all PHP files to UTF-8 no BOM with CRLF line endings
$projectRoot = "c:\opt\kotvrf\CatVRF"
$phpFiles = Get-ChildItem -Path $projectRoot -Filter "*.php" -Recurse -ErrorAction SilentlyContinue

Write-Host "Found PHP files: $($phpFiles.Count)"
Write-Host "Converting to UTF-8 no BOM + CRLF..." -ForegroundColor Yellow

$converted = 0
$errors = 0

foreach ($file in $phpFiles) {
    try {
        # Read file as bytes
        $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
        
        # Check for UTF-8 BOM (0xEF 0xBB 0xBF)
        $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
        
        # Decode to string
        $encoding = [System.Text.Encoding]::UTF8
        if ($hasBom) {
            $text = $encoding.GetString($bytes, 3, $bytes.Length - 3)
        } else {
            $text = $encoding.GetString($bytes)
        }
        
        # Normalize line endings: CRLF -> LF -> CR -> LF -> CRLF
        $text = $text -replace "`r`n", "`n"
        $text = $text -replace "`r", "`n"
        $text = $text -replace "`n", "`r`n"
        
        # Encode to UTF-8 WITHOUT BOM
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        $newBytes = $utf8NoBom.GetBytes($text)
        
        # Write back
        [System.IO.File]::WriteAllBytes($file.FullName, $newBytes)
        $converted++
        
        if ($converted % 500 -eq 0) {
            Write-Host "  Processed: $converted files..."
        }
    }
    catch {
        Write-Host "  ERROR in $($file.Name): $_" -ForegroundColor Red
        $errors++
    }
}

Write-Host ""
Write-Host "COMPLETE!" -ForegroundColor Green
Write-Host "Converted: $converted files"
Write-Host "Errors: $errors files"
Write-Host "Total: $($phpFiles.Count) files processed"
