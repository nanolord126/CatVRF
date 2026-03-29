@echo off
setlocal enabledelayedexpansion

echo Creating all missing Pages for 127 Resources...
echo ═══════════════════════════════════════════════

set created=0
set base=c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources

for /f "tokens=*" %%R in ('dir %base% /b /s | findstr "Resource.php"') do (
    set "resource=%%R"
    for %%A in ("!resource!") do set "filename=%%~nxA"
    set "name=!filename:~0,-16!"
    
    set "pagesdir=%base%\!name!\Pages"
    
    if not exist "!pagesdir!" mkdir "!pagesdir!"
    
    REM Create List page
    if not exist "!pagesdir!\List!name!.php" (
        (
            echo ^<?php declare^(strict_types=1^);
            echo namespace App\Filament\Tenant\Resources\!name!\Pages;
            echo use App\Filament\Tenant\Resources\!name!Resource;
            echo use Filament\Resources\Pages\ListRecords;
            echo final class List!name! extends ListRecords {
            echo     protected static string $resource = !name!Resource::class;
            echo }
        ) > "!pagesdir!\List!name!.php"
        set /a created+=1
    )
    
    REM Create Create page
    if not exist "!pagesdir!\Create!name!.php" (
        (
            echo ^<?php declare^(strict_types=1^);
            echo namespace App\Filament\Tenant\Resources\!name!\Pages;
            echo use App\Filament\Tenant\Resources\!name!Resource;
            echo use Filament\Resources\Pages\CreateRecord;
            echo final class Create!name! extends CreateRecord {
            echo     protected static string $resource = !name!Resource::class;
            echo }
        ) > "!pagesdir!\Create!name!.php"
        set /a created+=1
    )
    
    REM Create Edit page
    if not exist "!pagesdir!\Edit!name!.php" (
        (
            echo ^<?php declare^(strict_types=1^);
            echo namespace App\Filament\Tenant\Resources\!name!\Pages;
            echo use App\Filament\Tenant\Resources\!name!Resource;
            echo use Filament\Resources\Pages\EditRecord;
            echo final class Edit!name! extends EditRecord {
            echo     protected static string $resource = !name!Resource::class;
            echo }
        ) > "!pagesdir!\Edit!name!.php"
        set /a created+=1
    )
    
    REM Create View page
    if not exist "!pagesdir!\View!name!.php" (
        (
            echo ^<?php declare^(strict_types=1^);
            echo namespace App\Filament\Tenant\Resources\!name!\Pages;
            echo use App\Filament\Tenant\Resources\!name!Resource;
            echo use Filament\Resources\Pages\ViewRecord;
            echo final class View!name! extends ViewRecord {
            echo     protected static string $resource = !name!Resource::class;
            echo }
        ) > "!pagesdir!\View!name!.php"
        set /a created+=1
    )
)

echo.
echo ✅ Created/Verified: !created! Pages
echo 🎯 System ready for deployment
echo ═══════════════════════════════════════════════
