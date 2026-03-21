<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Logistics\Models\B2BLogisticsOrder;
use Illuminate\Database\Eloquent\Builder;

class LogisticsResource extends Resource
{
    protected static ?string $model = B2BLogisticsOrder::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Add your schema here
            Forms\Components\TextInput::make('name')->required(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
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