# Audit Filament Pages PowerShell Script
$projectRoot = "c:\opt\kotvrf\CatVRF"
$resourcesPath = "$projectRoot\app\Filament\Tenant\Resources"

# Найти все Pages файлы
$pagesFiles = Get-ChildItem -Path "$resourcesPath\*\Pages\*.php" -Recurse | Sort-Object FullName

$issues = @{
    'namespace_errors' = @()
    'missing_resources' = @()
    'invalid_model_paths' = @()
    'missing_models' = @()
    'invalid_resource_configs' = @()
    'import_errors' = @()
    'parse_errors' = @()
}

$totalPages = 0
$validPages = 0
$brokenPages = 0
$pageDetails = @()

Write-Host ("=" * 110)
Write-Host "FILAMENT PAGES AUDIT REPORT"
Write-Host ("=" * 110)
Write-Host ""

foreach ($file in $pagesFiles) {
    $totalPages++
    $filePath = $file.FullName
    $relativePath = $filePath.Substring($projectRoot.Length + 1)
    
    $content = [System.IO.File]::ReadAllText($filePath, [System.Text.Encoding]::UTF8)
    
    $pageInfo = @{
        'file' = $relativePath
        'valid' = $true
        'errors' = @()
    }
    
    # Парсим PHP файл с помощью регулярных выражений
    $namespace = $null
    $useStatements = @{}
    $className = $null
    
    # Namespace
    if ($content -match 'namespace\s+([^;]+);') {
        $namespace = $matches[1].Trim()
    }
    
    # Use statements
    $useMatches = [regex]::Matches($content, 'use\s+([^;]+);')
    foreach ($useMatch in $useMatches) {
        $useLine = $useMatch.Groups[1].Value.Trim()
        
        if ($useLine -match '(.+?)\s+as\s+(\w+)$') {
            $path = $matches[1].Trim()
            $alias = $matches[2].Trim()
            $useStatements[$alias] = $path
        } else {
            # Берем последнюю часть как имя
            $parts = $useLine -split '\\\'
            $simpleName = $parts[-1]
            $useStatements[$simpleName] = $useLine
        }
    }
    
    # Class name
    if ($content -match 'class\s+(\w+)') {
        $className = $matches[1]
    }
    
    # Проверяем namespace корректность
    $resourcePath = [System.IO.Path]::GetDirectoryName($filePath)
    $expectedNamespace = ($resourcePath -replace '\\', '\' -replace [regex]::Escape($resourcesPath), '') -replace '\\', '\'
    $expectedNamespace = 'App\Filament\Tenant\Resources' + $expectedNamespace
    
    if ($namespace -ne $expectedNamespace) {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Namespace: expected '$expectedNamespace', got '$namespace'"
        $issues['namespace_errors'] += @{
            'file' = $relativePath
            'expected' = $expectedNamespace
            'actual' = $namespace
        }
    }
    
    # Найти Resource класс
    $resourceClass = $null
    foreach ($alias in $useStatements.Keys) {
        $fullPath = $useStatements[$alias]
        if ($fullPath -like '*Resource' -or $alias -like '*Resource') {
            $resourceClass = $fullPath
            break
        }
    }
    
    # Если не нашли, ищем в коде
    if (-not $resourceClass) {
        if ($content -match 'protected\s+static\s+string\s+\$resource\s*=\s*(\w+)::class') {
            $resourceName = $matches[1]
            if ($useStatements.ContainsKey($resourceName)) {
                $resourceClass = $useStatements[$resourceName]
            } else {
                $resourceClass = $resourceName
            }
        }
    }
    
    if (-not $resourceClass) {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Resource class not found"
        $issues['missing_resources'] += @{
            'file' = $relativePath
            'error' = 'Not found'
        }
        $brokenPages++
        $pageDetails += $pageInfo
        continue
    }
    
    # Проверяем существование файла Resource
    $resourceFile = $null
    if ($resourceClass -like 'App\*') {
        $resourceFile = $projectRoot + '\app\' + ($resourceClass -replace 'App\\', '' -replace '\\', '\') + '.php'
    } else {
        # Пытаемся вывести из структуры папок
        if ($filePath -match 'Resources\\([^\\]+)\\Pages') {
            $resourceName = $matches[1]
            $resourceFile = "$resourcesPath\$resourceName\$resourceName.php"
        }
    }
    
    if (-not $resourceFile -or -not (Test-Path $resourceFile)) {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Resource file not found: $resourceFile"
        $issues['missing_resources'] += @{
            'file' = $relativePath
            'resource' = $resourceClass
            'path' = $resourceFile
        }
        $brokenPages++
        $pageDetails += $pageInfo
        continue
    }
    
    # Проверяем содержимое Resource
    $resourceContent = [System.IO.File]::ReadAllText($resourceFile, [System.Text.Encoding]::UTF8)
    
    if ($resourceContent -match 'protected\s+static\s+\?string\s+\$model\s*=\s*([^;]+);') {
        $modelClass = ($matches[1] -replace '::class', '').Trim()
    } else {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Resource missing \$model property"
        $issues['invalid_resource_configs'] += @{
            'resource' = $resourceClass
            'file' = [System.IO.Path]::GetFileName($resourceFile)
            'issue' = 'Missing $model'
        }
        $brokenPages++
        $pageDetails += $pageInfo
        continue
    }
    
    # Проверяем Model файл
    if ($modelClass -like 'App\*') {
        $modelFile = $projectRoot + '\app\' + ($modelClass -replace 'App\\', '' -replace '\\', '\') + '.php'
    } else {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Invalid model path: $modelClass"
        $issues['invalid_model_paths'] += @{
            'resource' = $resourceClass
            'model' = $modelClass
        }
        $brokenPages++
        $pageDetails += $pageInfo
        continue
    }
    
    if (-not (Test-Path $modelFile)) {
        $pageInfo['valid'] = $false
        $pageInfo['errors'] += "Model file not found: $modelClass"
        $issues['missing_models'] += @{
            'resource' = $resourceClass
            'model' = $modelClass
            'expected_path' = $modelFile
        }
        $brokenPages++
        $pageDetails += $pageInfo
        continue
    }
    
    # Проверяем импорты
    foreach ($alias in $useStatements.Keys) {
        $fullPath = $useStatements[$alias]
        
        # Пропускаем встроенные пакеты
        if ($fullPath -like 'Filament\*' -or 
            $fullPath -like 'Illuminate\*' -or 
            $fullPath -like 'Laravel\*' -or 
            $fullPath -like 'Symfony\*' -or 
            $fullPath -like 'PHPUnit\*') {
            continue
        }
        
        if (-not ($fullPath -like 'App\*')) {
            continue
        }
        
        $importFile = $projectRoot + '\app\' + ($fullPath -replace 'App\\', '' -replace '\\', '\') + '.php'
        
        if (-not (Test-Path $importFile)) {
            $pageInfo['valid'] = $false
            $pageInfo['errors'] += "Import not found: $fullPath"
            $issues['import_errors'] += @{
                'file' = $relativePath
                'import' = $fullPath
            }
        }
    }
    
    if ($pageInfo['valid']) {
        $validPages++
    } else {
        $brokenPages++
    }
    
    $pageDetails += $pageInfo
}

# Вывод результатов
Write-Host "SUMMARY:"
Write-Host "--------"
Write-Host ("Total Pages:   {0:3}" -f $totalPages)
Write-Host ("Valid Pages:   {0:3} ({1:0.0}%)" -f $validPages, ($totalPages -gt 0 ? ($validPages / $totalPages * 100) : 0))
Write-Host ("Broken Pages:  {0:3} ({1:0.0}%)" -f $brokenPages, ($totalPages -gt 0 ? ($brokenPages / $totalPages * 100) : 0))
Write-Host ""

# Детальные проблемы
$errorCategories = @{
    'namespace_errors' = 'NAMESPACE ERRORS'
    'missing_resources' = 'MISSING OR INVALID RESOURCES'
    'invalid_model_paths' = 'INVALID MODEL PATHS'
    'missing_models' = 'MISSING MODELS'
    'invalid_resource_configs' = 'INVALID RESOURCE CONFIGURATIONS'
    'import_errors' = 'IMPORT ERRORS'
    'parse_errors' = 'PARSE ERRORS'
}

foreach ($key in $errorCategories.Keys) {
    if ($issues[$key].Count -gt 0) {
        Write-Host ""
        Write-Host ("=" * 110)
        Write-Host ($errorCategories[$key] + " (" + $issues[$key].Count + ")")
        Write-Host ("=" * 110)
        
        foreach ($issue in $issues[$key]) {
            Write-Host ""
            foreach ($k in $issue.Keys) {
                Write-Host ("  $k`: " + $issue[$k])
            }
        }
    }
}

# Список всех broken pages
if ($brokenPages -gt 0) {
    Write-Host ""
    Write-Host ("=" * 110)
    Write-Host "ALL BROKEN PAGES DETAILS"
    Write-Host ("=" * 110)
    
    $count = 0
    foreach ($page in $pageDetails) {
        if (-not $page['valid']) {
            $count++
            Write-Host ""
            Write-Host ("[{0}] {1}" -f $count, $page['file'])
            foreach ($error in $page['errors']) {
                Write-Host ("  ✗ {0}" -f $error)
            }
        }
    }
}

Write-Host ""
Write-Host ("=" * 110)
Write-Host "END OF AUDIT"
Write-Host ("=" * 110)
