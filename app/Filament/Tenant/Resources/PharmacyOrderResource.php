<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Pharmacy\Models\PharmacyOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PharmacyOrderResource extends Resource
{
    protected static ?string $model = PharmacyOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Pharmacy Orders';
    protected static ?string $navigationGroup = 'Pharmacy';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('prescription_id')
                    ->relationship('prescription', 'id'),
                Textarea::make('medicines')
                    ->required()
                    ->helperText('JSON format: [{"medicine_id": 1, "quantity": 2}]')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'verified' => 'Verified', 'delivered' => 'Delivered'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('prescription_id')->sortable(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('status')->badge(),
                TextColumn::make('delivery_date')->date(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with('prescription');
    }
}
