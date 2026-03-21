<?php
$auditStr = shell_exec("php audit_deep_verticals.php");
$verticals = array_map('basename', glob(__DIR__ . '/app/Domains/*', GLOB_ONLYDIR));

foreach ($verticals as $vertical) {
    if ($vertical === "AI" || $vertical === "Shared" || $vertical === "SEO") continue;
    
    $resourcePath = __DIR__ . "/app/Filament/Tenant/Resources/{$vertical}Resource.php";
    if (!file_exists($resourcePath)) {
        // Try to find a model to use
        $modelsPath = __DIR__ . "/app/Domains/{$vertical}/Models";
        $modelClass = "";
        if (file_exists($modelsPath)) {
            $models = glob($modelsPath . '/*.php');
            if (count($models) > 0) {
                // Pick the first model, or one matching vertical name
                $modelClass = basename($models[0], '.php');
                foreach ($models as $m) {
                    $b = basename($m, '.php');
                    if (stripos($b, $vertical) !== false) {
                        $modelClass = $b; break;
                    }
                }
            }
        }
        
        if (empty($modelClass)) $modelClass = $vertical; // fallback
        
        $content = <<<PHP
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\\{$vertical}\\Models\\{$modelClass};
use Illuminate\Database\Eloquent\Builder;

class {$vertical}Resource extends Resource
{
    protected static ?string \$model = {$modelClass}::class;
    
    protected static ?string \$navigationIcon = 'heroicon-o-collection';
    
    public static function form(Form \$form): Form
    {
        return \$form->schema([
            // Add your schema here
            Forms\Components\TextInput::make('name')->required(),
        ]);
    }
    
    public static function table(Table \$table): Table
    {
        return \$table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }
}
PHP;
        if (!is_dir(dirname($resourcePath))) {
            mkdir(dirname($resourcePath), 0777, true);
        }
        file_put_contents($resourcePath, $content);
        echo "Created resource for {$vertical}: {$resourcePath}\n";
    }
}
