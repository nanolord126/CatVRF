@echo off
REM Full Project Audit - Excluding vendor and working directories
REM Files < 60 lines = FAIL

setlocal enabledelayedexpansion

set "projectRoot=%cd%"
set "failCount=0"
set "passCount=0"
set "totalFiles=0"

REM Directories to exclude
set "excludeDirs=vendor node_modules build dist storage\framework .git .github bootstrap\cache public\build resources\dist"

echo.
echo ╔═══════════════════════════════════════════════════════════╗
echo ║       FULL PROJECT AUDIT - COMPLETENESS CHECK             ║
echo ║          (Excluding vendor and working dirs)              ║
echo ╚═══════════════════════════════════════════════════════════╝
echo.

REM Check PHP files
echo Auditing PHP files...
for /r "%projectRoot%\app" %%F in (*.php) do (
    call :checkFile "%%F"
)

REM Check Blade files
echo Auditing Blade files...
for /r "%projectRoot%\resources\views" %%F in (*.blade.php) do (
    call :checkFile "%%F"
)

REM Check Vue files
echo Auditing Vue files...
for /r "%projectRoot%\resources\js" %%F in (*.vue) do (
    call :checkFile "%%F"
)

REM Check config files
echo Auditing Config files...
for /r "%projectRoot%\config" %%F in (*.php) do (
    call :checkFile "%%F"
)

REM Check database files
echo Auditing Database files...
for /r "%projectRoot%\database" %%F in (*.php) do (
    call :checkFile "%%F"
)

REM Check routes
echo Auditing Route files...
for /r "%projectRoot%\routes" %%F in (*.php) do (
    call :checkFile "%%F"
)

echo.
echo ╔═══════════════════════════════════════════════════════════╗
echo ║                    AUDIT RESULTS                          ║
echo ╚═══════════════════════════════════════════════════════════╝
echo.
echo Total Files Scanned: %totalFiles%
echo PASS (60+ lines): %passCount%
echo FAIL (^<60 lines): %failCount%
echo.

if %failCount% gtr 0 (
    echo WARNING: %failCount% files are incomplete (less than 60 lines)
    echo These files need to be completed before production deployment.
) else (
    echo All files are complete and production-ready!
)

goto :eof

:checkFile
setlocal enabledelayedexpansion
set "file=%~1"
set "fileName=%~nx1"

REM Skip if in excluded directories
set "skip=0"
for %%D in (%excludeDirs%) do (
    echo.!file! | find /i "\%%D\" >nul && set "skip=1"
)

if !skip! equ 1 goto :endCheck

set /a totalFiles+=1

REM Count lines
for /f %%C in ('find /c /v "" ^< "!file!"') do set "lineCount=%%C"

if !lineCount! lss 60 (
    set /a failCount+=1
    echo [FAIL] !fileName! - !lineCount! lines
) else (
    set /a passCount+=1
)

:endCheck
endlocal & set /a totalFiles=%totalFiles% & set /a failCount=%failCount% & set /a passCount=%passCount%
exit /b
