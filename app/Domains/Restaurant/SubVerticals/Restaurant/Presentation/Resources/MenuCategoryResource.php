<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Infrastructure\Models\MenuCategoryModel;

final class MenuCategoryResource extends Resource
{
    protected static ?string $model = MenuCategoryModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Категории меню';

    protected static ?string $modelLabel = 'Категория меню';

    protected static ?string $navigationGroup = 'Ресторан';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о категории')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\FileUpload::make('image_url')
                            ->image()
                            ->directory('menu-categories')
                            ->label('Изображение'),

                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Родительская категория'),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->label('Порядок отображения'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активна'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),

                Tables\Columns\TextColumn::make('parent.name')
                    ->searchable()
                    ->sortable()
                    ->label('Родительская категория'),

                Tables\Columns\ImageColumn::make('image_url')
                    ->circular()
                    ->label('Изображение'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна'),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable()
                    ->label('Порядок'),

                Tables\Columns\TextColumn::make('menuItems_count')
                    ->counts('menuItems')
                    ->label('Блюд'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создано'),
            ])
            ->defaultSort('display_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),

                Tables\Filters\SelectFilter::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label('Родительская категория'),
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
            'index' => \Modules\Restaurant\Presentation\Resources\MenuCategoryResource\Pages\ListMenuCategories::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\MenuCategoryResource\Pages\CreateMenuCategory::route('/create'),
            'edit' => \Modules\Restaurant\Presentation\Resources\MenuCategoryResource\Pages\EditMenuCategory::route('/{record}/edit'),
        ];
    }
}
