<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\OfficeCatering\Models\CorporateOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class CorporateOrderResource extends Resource
{
    protected static ?string $model = CorporateOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Catering Orders';
    protected static ?string $navigationGroup = 'OfficeCatering';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->required(),
                Select::make('menu_id')
                    ->relationship('menu', 'name')
                    ->required(),
                TextInput::make('portions')->numeric()->required()->minValue(1),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'delivered' => 'Delivered'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.company_name')->sortable(),
                TextColumn::make('portions')->sortable(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('is_recurring')->boolean(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('is_recurring')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with('client', 'menu');
    }
}
