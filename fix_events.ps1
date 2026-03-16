$dir = "c:\opt\kotvrf\CatVRF\app\Events"
$fixed = 0

Get-ChildItem "$dir\*.php" | ForEach-Object {
    $path = $_.FullName
    $text = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)
    $orig = $text

    if ($text -match 'LogManager') {
        $text = $text -replace 'use App\\Services\\LogManager;', 'use Illuminate\Support\Facades\Log;'
        $text = $text -replace "app\(\\\App\\\Services\\\LogManager::\w+\)->channel\('audit'\)->info\(", "Log::channel('audit')->info("
        $text = $text -replace "app\(\\\App\\\Services\\\LogManager::\w+\)->channel\('errors'\)->error\(", "Log::channel('errors')->error("
        
        if ($text -ne $orig) {
            [System.IO.File]::WriteAllText($path, $text, [System.Text.Encoding]::UTF8)
            Write-Host "✓ $($_.Name)"
            $fixed++
        }
    }
}

Write-Host "Fixed $fixed files"
