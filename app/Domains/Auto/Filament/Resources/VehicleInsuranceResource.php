<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;

final class VehicleInsuranceResource extends Resource
{

    protected static ?string $model = VehicleInsurance::class;

        protected static ?string $navigationLabel = 'Страхование';

        protected static ?string $pluralModelLabel = 'Страховки';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о страховке')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('policy_type')
                            ->label('Тип полиса')
                            ->options([
                                'osago' => 'ОСАГО',
                                'kasko' => 'КАСКО',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('policy_number')
                            ->label('Номер полиса')
                            ->required()
                            ->unique(VehicleInsurance::class, 'policy_number', ignoreRecord: true),

                        Forms\Components\TextInput::make('insurance_company')
                            ->label('Страховая компания')
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->required(),

                        Forms\Components\TextInput::make('premium_amount')
                            ->label('Стоимость полиса (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'expired' => 'Истёк',
                                'cancelled' => 'Отменён',
                            ])
                            ->default('active')
                            ->required(),
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

                    Tables\Columns\TextColumn::make('policy_type')
                        ->label('Тип')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'kasko' => 'success',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('policy_number')
                        ->label('Номер полиса')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('insurance_company')
                        ->label('Страховая')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('end_date')
                        ->label('Действителен до')
                        ->date('d.m.Y')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('premium_amount')
                        ->label('Стоимость')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'expired' => 'danger',
                            'cancelled' => 'gray',
                            default => 'gray',
                        }),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('policy_type')
                        ->label('Тип полиса')
                        ->options([
                            'osago' => 'ОСАГО',
                            'kasko' => 'КАСКО',
                        ]),

                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'active' => 'Активен',
                            'expired' => 'Истёк',
                            'cancelled' => 'Отменён',
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
                'index' => Pages\ListVehicleInsurances::route('/'),
                'create' => Pages\CreateVehicleInsurance::route('/create'),
                'edit' => Pages\EditVehicleInsurance::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
