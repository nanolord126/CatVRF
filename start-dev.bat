@echo off
REM Quick start script for CatVRF real-time analytics
REM Запускает все необходимые серверы в одной команде

echo.
echo ==========================================
echo CatVRF Real-Time Analytics - Quick Start
echo ==========================================
echo.

REM Проверка зависимостей
echo Checking dependencies...

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: PHP not found in PATH
    exit /b 1
)

where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: npm not found in PATH
    exit /b 1
)

echo ✓ PHP found
echo ✓ npm found
echo.

REM Переход в директорию проекта
cd /d c:\opt\kotvrf\CatVRF
if %errorlevel% neq 0 (
    echo Error: Failed to change directory
    exit /b 1
)

echo Starting servers...
echo.

REM Запуск PHP сервера в отдельном окне
echo [1/3] Starting PHP dev server on http://127.0.0.1:8000...
start "PHP Dev Server" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"
timeout /t 2 /nobreak

REM Запуск Vite dev сервера в отдельном окне
echo [2/3] Starting Vite dev server on http://localhost:5173...
start "Vite Dev Server" cmd /k "npm run dev"
timeout /t 3 /nobreak

REM Запуск Reverb WebSocket сервера в отдельном окне
echo [3/3] Starting Reverb WebSocket server on ws://localhost:8080...
start "Reverb WebSocket" cmd /k "php artisan reverb:start"
timeout /t 2 /nobreak

echo.
echo ==========================================
echo Services started successfully!
echo ==========================================
echo.
echo Web Application:
echo   → http://127.0.0.1:8000
echo.
echo Vite Dev Server:
echo   → http://localhost:5173
echo.
echo Analytics Dashboard:
echo   → http://127.0.0.1:8000/analytics/heatmaps
echo.
echo WebSocket (Reverb):
echo   → ws://localhost:8080
echo.
echo Real-Time Polling:
echo   → Enabled (30 second intervals)
echo   → Auto-refresh active
echo.
echo Logs:
echo   → tail -f storage/logs/laravel.log | grep -i polling
echo.
echo Press CTRL+C to stop any service
echo ==========================================
echo.

pause
