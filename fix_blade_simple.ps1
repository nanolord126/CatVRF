param()

$bladeDir = 'resources/views'
$files = Get-ChildItem -Path $bladeDir -Filter '*.blade.php' -Recurse

$converted = 0
$failed = 0

Write-Host "Converting Blade files to UTF-8 CRLF..." -ForegroundColor Green

foreach ($file in $files) {
    try {
        # Read content
        $content = [System.IO.File]::ReadAllText($file.FullName)
        
        # Normalize line endings to CRLF
        $content = $content -replace "`r`n", "`n"
        $content = $content -replace "`n", "`r`n"
        
        # Write UTF-8 without BOM
        $utf8NoBOM = [System.Text.Encoding]::GetEncoding([System.Text.Encoding]::UTF8.CodePage, [System.Text.EncoderFallback]::ExceptionFallback, [System.Text.DecoderFallback]::ExceptionFallback)
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        
        Write-Host "OK: $($file.Name)" -ForegroundColor Green
        $converted++
    } catch {
        Write-Host "ERROR: $($file.Name) - $($_.Exception.Message)" -ForegroundColor Red
        $failed++
    }
}

Write-Host ""
Write-Host "Results: $converted converted, $failed failed" -ForegroundColor Yellow
