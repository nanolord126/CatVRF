# PowerShell script to convert all PHP files to UTF-8 no BOM with CRLF
$phpFiles = Get-ChildItem -Path "." -Filter "*.php" -Recurse -File

$count = 0
foreach ($file in $phpFiles) {
    try {
        # Read file content
        $content = [System.IO.File]::ReadAllText($file.FullName)
        
        # Convert line endings to CRLF
        $content = $content -replace "`r`n", "`n"  # First normalize to LF
        $content = $content -replace "`n", "`r`n"  # Then convert to CRLF
        
        # Write with UTF-8 without BOM encoding
        $utf8NoBOM = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText($file.FullName, $content, $utf8NoBOM)
        
        $count++
        Write-Host "✓ $($file.FullName)"
    }
    catch {
        Write-Host "✗ $($file.FullName): $_"
    }
}

Write-Host "`nConverted $count files to UTF-8 no BOM with CRLF"
