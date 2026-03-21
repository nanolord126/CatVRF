cd c:\opt\kotvrf\CatVRF

echo "========================================"
echo "3D SYSTEM DEPLOYMENT STARTING"
echo "========================================"

REM 1. Clear cache
echo.
echo [1] Clearing cache...
php artisan cache:clear
echo OK

REM 2. Create directories
echo.
echo [2] Creating storage directories...
if not exist "storage\app\public\3d-models" mkdir storage\app\public\3d-models
if not exist "storage\app\public\3d-models\Jewelry" mkdir storage\app\public\3d-models\Jewelry
if not exist "storage\app\public\3d-models\Hotels" mkdir storage\app\public\3d-models\Hotels
if not exist "storage\app\public\3d-models\Furniture" mkdir storage\app\public\3d-models\Furniture
if not exist "storage\app\public\3d-previews" mkdir storage\app\public\3d-previews
echo OK

REM 3. Symlink
echo.
echo [3] Creating storage symlink...
php artisan storage:link
echo OK

REM 4. Warm cache
echo.
echo [4] Warming cache...
php artisan config:cache
php artisan route:cache
echo OK

REM 5. Summary
echo.
echo ========================================
echo 3D SYSTEM READY
echo ========================================
echo.
echo Features:
echo   - 7 Core 3D Services
echo   - 12+ API Endpoints
echo   - 6 Demo Products
echo   - Mobile + AR Support
echo.
echo Starting server on http://localhost:8000
echo Open browser to: http://localhost:8000/3d-demo
echo.
echo Press Ctrl+C to stop server
echo.

timeout /t 2

php artisan serve --port=8000
