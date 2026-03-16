# Скрипт для исправления Marketplace Page файлов в продакшен стандарты
# Заменяет Illuminate\Log\LogManager на App\Services\LogManager
# Заменяет boot() на __construct()
# Удаляет Gate параметры и использование

$files = Get-ChildItem -Path "app\Filament\Tenant\Resources\Marketplace\*\Pages\*.php" -Recurse -File

$fixedCount = 0
$errors = @()

foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
        $original = $content
        
        # Замена 1: Illuminate\Log\LogManager -> App\Services\LogManager
        $content = $content -replace 'use Illuminate\\Log\\LogManager;', 'use App\Services\LogManager;'
        
        # Замена 2: Удаление Gate импорта
        $content = $content -replace "use Illuminate\\Contracts\\Auth\\Access\\Gate;`r?`n", ""
        
        # Замена 3: Удаление Gate параметра из класса
        $content = $content -replace 'protected Gate \$gate;`r?`n', ''
        $content = $content -replace 'private Gate \$gate;`r?`n', ''
        
        # Замена 4: Замена boot() на __construct()
        # Для файлов с параметрами в boot()
        if ($content -match 'public function boot\(') {
            # Извлекаем параметры
            if ($content -match 'public function boot\((.*?)\): void \{') {
                $params = $matches[1]
                
                # Удаляем Gate из параметров
                $params = $params -replace ',?\s*Gate \$gate' , ''
                $params = $params -replace 'Gate \$gate,?\s*', ''
                
                # Заменяем boot на __construct и убираем параметры (будут использоваться app())
                $bootMethod = @"
    public function __construct()
    {
        parent::__construct();"@
                
                # Ищем что присваивается в boot()
                $content = $content -replace 'public function boot\([^)]*\): void \{', $bootMethod
            }
        }
        
        # Замена 5: Замена присваиваний в __construct
        # Заменяем `$this->guard = $guard;` на `$this->guard = app('auth');` и т.д.
        $content = $content -replace '\$this->guard = \$guard;', '$this->guard = app(''auth'');'
        $content = $content -replace '\$this->auth = \$auth;', '$this->auth = app(''auth'');'
        $content = $content -replace '\$this->log = \$log;', '$this->log = app(LogManager::class);'
        $content = $content -replace '\$this->db = \$db;', '$this->db = app(''db'');'
        $content = $content -replace '\$this->request = \$request;', '$this->request = app(''request'');'
        $content = $content -replace '\$this->rateLimiter = \$rateLimiter;', '$this->rateLimiter = app(''rate.limiter'');'
        
        # Замена 6: Изменения в authorizeAccess() - удаляем $this->gate->allows()
        $content = $content -replace '\$this->gate->allows\(', 'auth()->guard(''web'')->allows('
        
        if ($content -ne $original) {
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
            $fixedCount++
            Write-Host "✓ Fixed: $($file.Name)"
        }
    }
    catch {
        $errors += "$($file.Name): $($_.Exception.Message)"
    }
}

Write-Host "`n========================================="
Write-Host "Total fixed: $fixedCount files"
Write-Host "Total errors: $($errors.Count)"

if ($errors.Count -gt 0) {
    Write-Host "`nErrors:"
    $errors | ForEach-Object { Write-Host "  - $_" }
}
