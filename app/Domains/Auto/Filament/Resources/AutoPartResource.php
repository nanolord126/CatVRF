<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class AutoPartResource extends Resource
{

    protected static ?string $model = AutoPart::class;

        protected static ?string $navigationLabel = 'Запчасти';

        protected static ?string $pluralModelLabel = 'Запчасти';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о запчасти')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(AutoPart::class, 'sku', ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required(),

                        Forms\Components\TextInput::make('brand')
                            ->label('Производитель')
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Остатки')
                    ->schema([
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Текущий остаток')
                            ->numeric()
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('min_stock_threshold')
                            ->label('Минимальный остаток')
                            ->numeric()
                            ->required(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('sku')
                        ->label('SKU')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('brand')
                        ->label('Производитель')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('current_stock')
                        ->label('Остаток')
                        ->numeric()
                        ->color(fn ($state, $record) => $state < $record->min_stock_threshold ? 'danger' : 'success'),

                    Tables\Columns\TextColumn::make('min_stock_threshold')
                        ->label('Минимум')
                        ->numeric(),

                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->formatStateUsing(fn ($state) => ($state / 100) . ' ₽'),
                ])
                ->filters([
                    Tables\Filters\Filter::make('low_stock')
                        ->label('Низкий остаток')
                        ->query(fn ($query) => $query->whereColumn('current_stock', '<', 'min_stock_threshold')),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
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
                'index' => \App\Domains\Auto\Filament\Resources\AutoPartResource\Pages\ListAutoParts::route('/'),
                'create' => \App\Domains\Auto\Filament\Resources\AutoPartResource\Pages\CreateAutoPart::route('/create'),
                'edit' => \App\Domains\Auto\Filament\Resources\AutoPartResource\Pages\EditAutoPart::route('/{record}/edit'),
                'view' => \App\Domains\Auto\Filament\Resources\AutoPartResource\Pages\ViewAutoPart::route('/{record}'),
            ];
        }
}
