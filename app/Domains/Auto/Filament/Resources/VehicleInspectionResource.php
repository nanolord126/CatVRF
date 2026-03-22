<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use App\Domains\Auto\Models\VehicleInspection;
use App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class VehicleInspectionResource extends Resource
{
    protected static ?string $model = VehicleInspection::class;

    protected static ?string $navigationLabel = 'Техосмотр';

    protected static ?string $pluralModelLabel = 'Техосмотры';

    protected static ?string $navigationGroup = 'Авто';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация о техосмотре')
                ->schema([
                    Forms\Components\Select::make('vehicle_id')
                        ->label('Автомобиль')
                        ->relationship('vehicle', 'license_plate')
                        ->searchable()
                        ->required(),

                    Forms\Components\DatePicker::make('inspection_date')
                        ->label('Дата осмотра')
                        ->required(),

                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Действителен до')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'passed' => 'Пройден',
                            'failed' => 'Не пройден',
                            'pending' => 'Ожидает',
                        ])
                        ->default('pending')
                        ->required(),

                    Forms\Components\TextInput::make('certificate_number')
                        ->label('Номер сертификата')
                        ->visible(fn ($get) => $get('status') === 'passed'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Примечания')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.license_plate')
                    ->label('Автомобиль')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inspection_date')
                    ->label('Дата осмотра')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Действителен до')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Номер сертификата')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'passed' => 'Пройден',
                        'failed' => 'Не пройден',
                        'pending' => 'Ожидает',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleInspections::route('/'),
            'create' => Pages\CreateVehicleInspection::route('/create'),
            'edit' => Pages\EditVehicleInspection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
