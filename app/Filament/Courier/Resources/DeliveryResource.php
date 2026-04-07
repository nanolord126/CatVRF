<?php

declare(strict_types=1);

namespace App\Filament\Courier\Resources;

use App\Domains\Delivery\Domain\Entities\Delivery;
use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('from_address')->required(),
                Forms\Components\TextInput::make('to_address')->required(),
                Forms\Components\Select::make('status')
                    ->options(array_column(DeliveryStatus::cases(), 'value', 'value'))
                    ->required(),
                Forms\Components\Select::make('courier_id')
                    ->relationship('courier', 'name')
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('uuid')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('courier.name')->searchable(),
                Tables\Columns\TextColumn::make('from_address')->searchable(),
                Tables\Columns\TextColumn::make('to_address')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Courier\Resources\DeliveryResource\Pages\ListDeliveries::class,
            'create' => \App\Filament\Courier\Resources\DeliveryResource\Pages\CreateDelivery::class,
            'edit' => \App\Filament\Courier\Resources\DeliveryResource\Pages\EditDelivery::class,
            'view' => \App\Filament\Courier\Resources\DeliveryResource\Pages\ViewDelivery::class,
        ];
    }
}
