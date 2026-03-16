$files = Get-ChildItem -Path . -Recurse -Filter "*.php" -ErrorAction SilentlyContinue

$bomCount = 0
foreach ($file in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $relativePath = $file.FullName.Replace((Get-Location).Path + '\', '')
        Write-Host "BOM found: $relativePath" -ForegroundColor Yellow
        
        $contentNoBOM = $bytes[3..($bytes.Length-1)]
        [System.IO.File]::WriteAllBytes($file.FullName, $contentNoBOM)
        Write-Host "  OK - BOM removed" -ForegroundColor Green
        $bomCount++
    }
}

Write-Host ""
Write-Host "Total BOM files fixed: $bomCount" -ForegroundColor Cyan
