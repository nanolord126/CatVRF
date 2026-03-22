# АВТОМАТИЧЕСКАЯ ЗАГРУЗКА В GITHUB
# Использование: .\upload_beauty_to_github.ps1 <ваш-github-username> <токен>

param(
    [Parameter(Mandatory=$true)]
    [string]$Username,
    
    [Parameter(Mandatory=$true)]
    [string]$Token
)

Write-Host "=== ЗАГРУЗКА BEAUTY MODULE В GITHUB ===" -ForegroundColor Green
Write-Host ""

# Проверка git
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "✗ Git не установлен!" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Git найден" -ForegroundColor Green

# Добавление remote
$repoUrl = "https://${Username}:${Token}@github.com/${Username}/CatVRF-Beauty.git"
Write-Host "Добавление remote..." -ForegroundColor Yellow

git remote remove origin 2>$null
git remote add origin $repoUrl

# Коммит
Write-Host "Создание коммита..." -ForegroundColor Yellow
git add .
git commit -m "Beauty module: Production-ready after LUTY MODE 2.0 audit" 2>$null

# Push
Write-Host "Загрузка в GitHub..." -ForegroundColor Yellow
git branch -M main
git push -u origin main --force

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓ УСПЕШНО ЗАГРУЖЕНО!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Ваш репозиторий:" -ForegroundColor Cyan
    Write-Host "https://github.com/${Username}/CatVRF-Beauty" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "✗ Ошибка загрузки" -ForegroundColor Red
    Write-Host "Проверьте токен и имя пользователя" -ForegroundColor Yellow
}
