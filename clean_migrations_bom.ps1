# Проверка и удаление BOM из миграций
$paths = @(
    "C:\opt\kotvrf\CatVRF\database\migrations\tenant",
    "C:\opt\kotvrf\CatVRF\database\migrations"
)

$bomRemoved = 0
$duplicates = @{}

foreach ($path in $paths) {
    Get-ChildItem -Path $path -Filter "*.php" -File | ForEach-Object {
        $file = $_
        $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
        
        # Проверка BOM
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191) {
            $newBytes = $bytes[3..($bytes.Length - 1)]
            [System.IO.File]::WriteAllBytes($file.FullName, $newBytes)
            $bomRemoved++
            Write-Host "Removed BOM: $($file.Name)"
        }
        
        # Проверка на одну строку (compression)
        $content = [System.IO.File]::ReadAllText($file.FullName)
        $lineCount = ($content -split "`n").Count
        if ($lineCount -lt 5) {
            Write-Host "WARNING: $($file.Name) has only $lineCount lines (might be compressed)"
        }
    }
}

Write-Host "`nBOM removed from $bomRemoved files"
