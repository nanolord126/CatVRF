$files = Get-ChildItem -Path "app/Filament/Tenant/Resources/*/Pages/*.php" -Recurse
$fixed_count = 0
$affected_files = @()

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $original = $content
    $new_content = $content -replace "(?m)^    private\s+[\w\\]+(?:\s+\|\s+[\w\\]+)*\s+$\w+\s*;\s*\n", ""
    
    if ($new_content -ne $original) {
        [System.IO.File]::WriteAllText($file.FullName, $new_content, [System.Text.Encoding]::UTF8)
        $fixed_count++
        $affected_files += $file.Name
    }
}

Write-Host "Total files fixed: $fixed_count"
$affected_files | Sort-Object | ForEach-Object { Write-Host "  $_" }
