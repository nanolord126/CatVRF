<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class ServiceWarrantyResource extends Resource
{

    protected static ?string $model = ServiceWarranty::class;

        protected static ?string $navigationLabel = 'Гарантия на ремонт';

        protected static ?string $pluralModelLabel = 'Гарантии на ремонт';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о гарантии')
                    ->schema([
                        Forms\Components\Select::make('auto_service_order_id')
                            ->label('Заказ-наряд')
                            ->relationship('autoServiceOrder', 'id')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('warranty_number')
                            ->label('Номер гарантии')
                            ->required()
                            ->unique(ServiceWarranty::class, 'warranty_number', ignoreRecord: true),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),

                        Forms\Components\TextInput::make('warranty_months')
                            ->label('Срок гарантии (месяцы)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required(),

                        Forms\Components\TextInput::make('warranty_km')
                            ->label('Пробег по гарантии (км)')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('claim_status')
                            ->label('Статус претензии')
                            ->options([
                                'none' => 'Нет претензий',
                                'pending' => 'Рассматривается',
                                'approved' => 'Одобрено',
                                'rejected' => 'Отклонено',
                            ])
                            ->default('none')
                            ->required(),

                        Forms\Components\Textarea::make('claim_description')
                            ->label('Описание претензии')
                            ->visible(fn ($get) => $get('claim_status') !== 'none')
                            ->columnSpanFull(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('warranty_number')
                        ->label('Номер гарантии')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('vehicle.license_plate')
                        ->label('Автомобиль')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Клиент')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('start_date')
                        ->label('Начало')
                        ->date('d.m.Y')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('warranty_months')
                        ->label('Срок (мес)')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('warranty_km')
                        ->label('Пробег (км)')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('claim_status')
                        ->label('Претензия')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('claim_status')
                        ->label('Статус претензии')
                        ->options([
                            'none' => 'Нет претензий',
                            'pending' => 'Рассматривается',
                            'approved' => 'Одобрено',
                            'rejected' => 'Отклонено',
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
                'index' => \App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages\ListServiceWarranties::route('/'),
                'create' => \App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages\CreateServiceWarranty::route('/create'),
                'edit' => \App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages\EditServiceWarranty::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
