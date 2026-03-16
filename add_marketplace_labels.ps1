#!/usr/bin/env pwsh

# Marketplace resources that need labels
$resources = @{
    'app\Filament\Tenant\Resources\Marketplace\EventBookingResource.php' = @{ label = 'Бронирование'; pluralLabel = 'Бронирования' }
    'app\Filament\Tenant\Resources\Marketplace\FlowersItemResource.php' = @{ label = 'Товар'; pluralLabel = 'Товары (Цветы)' }
    'app\Filament\Tenant\Resources\Marketplace\FlowersOrderResource.php' = @{ label = 'Заказ'; pluralLabel = 'Заказы' }
    'app\Filament\Tenant\Resources\Marketplace\FlowersProductResource.php' = @{ label = 'Продукт'; pluralLabel = 'Продукты (Цветы)' }
    'app\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource.php' = @{ label = 'Прием'; pluralLabel = 'Приемы' }
    'app\Filament\Tenant\Resources\Marketplace\RestaurantDishResource.php' = @{ label = 'Блюдо'; pluralLabel = 'Блюда' }
    'app\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource.php' = @{ label = 'Меню'; pluralLabel = 'Меню' }
    'app\Filament\Tenant\Resources\Marketplace\RestaurantOrderResource.php' = @{ label = 'Заказ'; pluralLabel = 'Заказы' }
    'app\Filament\Tenant\Resources\Marketplace\RestaurantTableResource.php' = @{ label = 'Столик'; pluralLabel = 'Столики' }
    'app\Filament\Tenant\Resources\Marketplace\SupermarketProductResource.php' = @{ label = 'Товар'; pluralLabel = 'Товары' }
    'app\Filament\Tenant\Resources\Marketplace\TaxiTripResource.php' = @{ label = 'Поездка'; pluralLabel = 'Поездки' }
    'app\Filament\Tenant\Resources\Marketplace\Taxi\TaxiCarResource.php' = @{ label = 'Автомобиль'; pluralLabel = 'Автопарк' }
    'app\Filament\Tenant\Resources\Marketplace\Taxi\TaxiDispatcherConsole.php' = @{ label = 'Диспетчер'; pluralLabel = 'Диспетчерская' }
    'app\Filament\Tenant\Resources\Marketplace\Taxi\TaxiDriverResource.php' = @{ label = 'Водитель'; pluralLabel = 'Водители' }
    'app\Filament\Tenant\Resources\Marketplace\Taxi\TaxiFleetResource.php' = @{ label = 'Таксопарк'; pluralLabel = 'Таксопарки' }
}

foreach ($file in $resources.GetEnumerator()) {
    $filePath = $file.Key
    $labels = $file.Value
    
    $fullPath = "c:\opt\kotvrf\CatVRF\$filePath"
    
    if (Test-Path $fullPath) {
        Write-Host "📝 Processing: $filePath"
        
        $content = [System.IO.File]::ReadAllText($fullPath, [System.Text.Encoding]::UTF8)
        
        # Find the position after navigationIcon line
        if ($content -match 'protected static \?string \$navigationIcon[^\n]*\n') {
            $insertPos = $matches[0].Length
            $beforeMatch = $content.Substring(0, $content.IndexOf($matches[0]) + $insertPos)
            $afterMatch = $content.Substring($content.IndexOf($matches[0]) + $insertPos)
            
            $newLines = @"

    protected static ?string `$label = '$($labels.label)';

    protected static ?string `$pluralLabel = '$($labels.pluralLabel)';
"@
            
            $content = $beforeMatch + $newLines + $afterMatch
            
            [System.IO.File]::WriteAllText($fullPath, $content, [System.Text.Encoding]::UTF8)
            Write-Host "✅ Added labels to: $filePath"
        }
    }
}

Write-Host "✅ Done adding labels!"
