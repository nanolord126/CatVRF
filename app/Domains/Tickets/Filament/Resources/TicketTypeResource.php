<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use Filament\Resources\Resource;

final class TicketTypeResource extends Resource
{

    protected static ?string $model = TicketType::class;
        protected static ?string $navigationIcon = 'heroicon-o-ticket';
        protected static ?string $navigationLabel = 'Типы билетов';
        protected static ?int $navigationSort = 2;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\Select::make('event_id')
                                ->label('Событие')
                                ->relationship('event', 'title')
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label('Название')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('price')
                                ->label('Цена')
                                ->numeric()
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Параметры')
                        ->schema([
                            Forms\Components\TextInput::make('total_quantity')
                                ->label('Всего билетов')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('max_per_buyer')
                                ->label('Максимум на покупателя')
                                ->numeric(),
                            Forms\Components\DateTimePickerInput::make('sale_starts_at')
                                ->label('Начало продажи')
                                ->required(),
                            Forms\Components\DateTimePickerInput::make('sale_ends_at')
                                ->label('Конец продажи')
                                ->required(),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Активен'),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('event.title')
                        ->label('Событие')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('sold_quantity')
                        ->label('Продано'),
                    Tables\Columns\TextColumn::make('total_quantity')
                        ->label('Всего'),
                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Активен')
                        ->boolean(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Активные'),
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
                'index' => Pages\ListTicketTypes::route('/'),
                'create' => Pages\CreateTicketType::route('/create'),
                'edit' => Pages\EditTicketType::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()?->id)
                ->with(['event']);
        }
}
