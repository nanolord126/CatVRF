@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

cd /d c:\opt\kotvrf\CatVRF

echo.
echo ============================================
echo GITHUB PUSH - FINAL FIX
echo ============================================
echo.

git status -s | find "" /c >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo ✓ Files ready
) else (
    echo ✗ No files staged
    goto END
)

echo.
echo Pushing main to origin/master...
git push origin main:master --force -v

echo.
echo Checking result...
git status

echo.
echo ============================================
echo DONE!
echo Check: https://github.com/iyegorovskyi_clemny/CatVRF
echo ============================================

:END
pause
