$collapsedFiles = @()
$dirs = @(
    'C:\opt\kotvrf\CatVRF\app\Http\Controllers\Tenant',
    'C:\opt\kotvrf\CatVRF\app\Services',
    'C:\opt\kotvrf\CatVRF\app\Models',
    'C:\opt\kotvrf\CatVRF\app\Livewire'
)

foreach ($dir in $dirs) {
    if (Test-Path $dir) {
        Get-ChildItem -Path $dir -Filter '*.php' -File | ForEach-Object {
            $content = Get-Content -Path $_.FullName -Raw
            $lines = @($content -split "`r?`n" | Where-Object { $_.Trim() -ne '' })
            $size = (Get-Item $_.FullName).Length
            if ($size -gt 500 -and $lines.Count -lt 10) {
                $collapsedFiles += @{
                    Path = $_.FullName
                    Lines = $lines.Count
                    Size = $size
                }
            }
        }
    }
}

Write-Host "Total collapsed files found: $($collapsedFiles.Count)"
$collapsedFiles | Select-Object -First 50 | Format-Table -AutoSize
$collapsedFiles | Select-Object -ExpandProperty Path | Out-File -FilePath 'c:\opt\kotvrf\CatVRF\collapsed_files.txt'
