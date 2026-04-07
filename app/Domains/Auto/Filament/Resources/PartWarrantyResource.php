<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;

final class PartWarrantyResource extends Resource
{

    protected static ?string $model = PartWarranty::class;

        protected static ?string $navigationLabel = 'Гарантия на запчасти';

        protected static ?string $pluralModelLabel = 'Гарантии на запчасти';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о гарантии')
                    ->schema([
                        Forms\Components\Select::make('auto_part_id')
                            ->label('Запчасть')
                            ->relationship('autoPart', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('warranty_type')
                            ->label('Тип гарантии')
                            ->options([
                                'manufacturer' => 'Производителя',
                                'dealer' => 'Дилерская',
                                'extended' => 'Расширенная',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('warranty_number')
                            ->label('Номер гарантии')
                            ->required()
                            ->unique(PartWarranty::class, 'warranty_number', ignoreRecord: true),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),

                        Forms\Components\TextInput::make('warranty_months')
                            ->label('Срок гарантии (месяцы)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
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

                    Tables\Columns\TextColumn::make('autoPart.name')
                        ->label('Запчасть')
                        ->searchable()
                        ->limit(30),

                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Клиент')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('warranty_type')
                        ->label('Тип')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'dealer' => 'info',
                            'extended' => 'warning',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('start_date')
                        ->label('Начало')
                        ->date('d.m.Y')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('warranty_months')
                        ->label('Срок (мес)')
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
                    Tables\Filters\SelectFilter::make('warranty_type')
                        ->label('Тип гарантии')
                        ->options([
                            'manufacturer' => 'Производителя',
                            'dealer' => 'Дилерская',
                            'extended' => 'Расширенная',
                        ]),

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
                'index' => Pages\ListPartWarranties::route('/'),
                'create' => Pages\CreatePartWarranty::route('/create'),
                'edit' => Pages\EditPartWarranty::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
