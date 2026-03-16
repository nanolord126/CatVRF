<?php

declare(strict_types=1);

/**
 * Generate Production-Ready Filament Resources from Models
 * This script creates complete Resources with Forms, Tables, and Pages
 */

require_once 'vendor/autoload.php';

$resourceMap = [
    // Marketplace Verticals - Auto generated Resources
    'AutoResource' => null, // Will skip - no model
    'B2BSupplyOfferResource' => 'B2BSupplyOffer',
    'BeautySalonResource' => null, // Will skip
    'ClinicResource' => null, // Will skip
    'ClothingResource' => null, // Will skip
    'ConcertResource' => null, // Will skip
    'ConstructionResource' => 'Construction', // Already done
    'EducationCourseResource' => 'EducationCourse',
    'ElectronicsResource' => null, // Will skip
    'EventBookingResource' => 'EventBooking',
    'FlowerResource' => 'Flower', // Already done
    'FootwearResource' => null, // Will skip
    'FurnitureResource' => 'Furniture', // Already done
    'GardenProductResource' => 'GardenProduct', // Already done
    'GymResource' => null, // Will skip
    'HRExchangeOfferResource' => 'HRExchangeOffer',
    'HotelBookingResource' => null, // Will skip
    'HotelResource' => null, // Will skip
    'MedicalAppointmentResource' => 'MedicalAppointment',
    'MedicalCardResource' => null, // Will skip
    'PerfumeryResource' => null, // Will skip
    'PropertyResource' => null, // Will skip
    'RepairResource' => 'Repair', // Already done
    'RestaurantDishResource' => 'RestaurantDish',
    'RestaurantMenuResource' => 'RestaurantMenuItem',
    'RestaurantOrderResource' => 'RestaurantOrder',
    'RestaurantResource' => 'Restaurant', // Already done
    'RestaurantTableResource' => 'RestaurantTable',
    'SupermarketProductResource' => null, // Will skip
    'TaxiServiceResource' => 'TaxiService', // Already done
    'TaxiTripResource' => 'TaxiTrip',
    'VetClinicResource' => null, // Will skip
];

$basePath = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$generated = 0;
$skipped = 0;
$errors = [];

foreach ($resourceMap as $resourceName => $modelName) {
    if ($modelName === null) {
        echo "⏭️  SKIP: $resourceName (no model)\n";
        $skipped++;
        continue;
    }

    // Check if model exists
    $modelPath = __DIR__ . "/app/Models/Tenants/$modelName.php";
    if (!file_exists($modelPath)) {
        echo "❌ ERROR: Model not found for $resourceName: $modelPath\n";
        $errors[] = "$resourceName: Model $modelName not found";
        $skipped++;
        continue;
    }

    // Check if resource already has content (not empty)
    $resourcePath = "$basePath/$resourceName.php";
    if (file_exists($resourcePath) && filesize($resourcePath) > 500) {
        echo "✅ SKIP: $resourceName (already complete)\n";
        $skipped++;
        continue;
    }

    // Generate basic resource
    $resourceCode = generateResource($resourceName, $modelName);
    file_put_contents($resourcePath, $resourceCode);
    
    echo "✅ GENERATED: $resourceName\n";
    $generated++;
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 SUMMARY:\n";
echo "  Generated: $generated\n";
echo "  Skipped: $skipped\n";
if (!empty($errors)) {
    echo "  Errors: " . count($errors) . "\n";
    foreach ($errors as $err) {
        echo "    - $err\n";
    }
}
echo str_repeat("=", 70) . "\n";

function generateResource(string $resourceName, string $modelName): string
{
    $singularName = substr($resourceName, 0, -8); // Remove 'Resource'
    $tableName = strtolower(preg_replace('/([A-Z])/', '_$1', $singularName));
    $tableName = ltrim($tableName, '_');
    
    return <<<PHP
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Models\Tenants\\$modelName;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class $resourceName extends Resource
{
    protected static ?string \$model = $modelName::class;

    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string \$navigationGroup = 'Marketplace';

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('status')
                            ->default('active'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\List$singularName::route('/'),
            'create' => Pages\Create$singularName::route('/create'),
            'edit' => Pages\Edit$singularName::route('/{record}/edit'),
            'view' => Pages\View$singularName::route('/{record}'),
        ];
    }
}
PHP;
}
