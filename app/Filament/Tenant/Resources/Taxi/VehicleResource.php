<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\VehicleResource;

use App\Domains\Auto\Taxi\Domain\Enums\VehicleClassEnum;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Taxi Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('brand')->required()->maxLength(100),
                Forms\Components\TextInput::make('model')->required()->maxLength(100),
                Forms\Components\TextInput::make('license_plate')->required()->unique(ignoreRecord: true)->maxLength(20),
                Forms\Components\Select::make('class')
                    ->options(
                        collect(VehicleClassEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray()
                    )
                    ->required(),
                Forms\Components\Toggle::make('is_in_use')->default(false),
                Forms\Components\Hidden::make('tenant_id')->default(fn () => filament()->getTenant()?->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')->searchable(),
                Tables\Columns\TextColumn::make('model')->searchable(),
                Tables\Columns\TextColumn::make('license_plate')->searchable(),
                Tables\Columns\TextColumn::make('class')->badge(),
                Tables\Columns\IconColumn::make('is_in_use')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class')
                    ->options(
                        collect(VehicleClassEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Taxi\VehicleResource\Pages\ListVehicles::route('/'),
            'create' => \App\Filament\Tenant\Resources\Taxi\VehicleResource\Pages\CreateVehicle::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Taxi\VehicleResource\Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
