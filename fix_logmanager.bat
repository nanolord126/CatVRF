@echo off
setlocal enabledelayedexpansion

set "dir=c:\opt\kotvrf\CatVRF\app\Events"
set count=0

for /r "%dir%" %%F in (*.php) do (
    powershell -NoProfile -Command "
    \$file = '%%F'
    \$text = [System.IO.File]::ReadAllText(\$file, [System.Text.Encoding]::UTF8)
    \$orig = \$text
    
    if (\$text -match 'LogManager') {
        \$text = \$text -replace 'use App\\Services\\LogManager;', 'use Illuminate\Support\Facades\Log;'
        \$text = \$text -replace \"app\(LogManager::class\)-\>channel\('audit'\)-\>info\(\", \"Log::channel('audit')->info(\"
        \$text = \$text -replace \"app\(LogManager::class\)-\>channel\('errors'\)-\>error\(\", \"Log::channel('errors')->error(\"
        \$text = \$text -replace \"app\(\\\App\\\Services\\\LogManager::class\)-\>channel\('audit'\)-\>info\(\", \"Log::channel('audit')->info(\"
        \$text = \$text -replace \"app\(\\\App\\\Services\\\LogManager::class\)-\>channel\('errors'\)-\>error\(\", \"Log::channel('errors')->error(\"
        
        if (\$text -ne \$orig) {
            [System.IO.File]::WriteAllText(\$file, \$text, [System.Text.Encoding]::UTF8)
            Write-Host '✓ ' + [System.IO.Path]::GetFileName(\$file)
        }
    }
    "
    set /a count+=1
)

echo.
echo Total processed: %count% files
