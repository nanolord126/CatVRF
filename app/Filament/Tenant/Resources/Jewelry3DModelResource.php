<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Jewelry\Models\Jewelry3DModel;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

final class Jewelry3DModelResource extends Resource
{
    protected static ?string $model = Jewelry3DModel::class;

    protected static ?string $slug = 'jewelry-3d-models';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Ювелирные изделия';

    protected static ?string $navigationLabel = '3D Модели';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('jewelry_item_id')
                            ->relationship('Ювелирные изделия', 'name')
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('model_url')
                            ->label('Ссылка на 3D-модель (GLB)')
                            ->url()
                            ->required(),

                        Forms\Components\TextInput::make('texture_url')
                            ->label('Ссылка на текстуру')
                            ->url(),

                        Forms\Components\TextInput::make('preview_image_url')
                            ->label('Ссылка на превью (картинка)')
                            ->url(),
                    ]),

                Forms\Components\Section::make('Детали модели')
                    ->schema([
                        Forms\Components\Select::make('material_type')
                            ->options([
                                'Золото' => 'Золото',
                                'Серебро' => 'Серебро',
                                'Платина' => 'Платина',
                                'rose_gold' => 'Розовое золото',
                            ])
                            ->default('Золото'),

                        Forms\Components\TextInput::make('weight_grams')
                            ->numeric()
                            ->step(0.01),

                        Forms\Components\TextInput::make('file_size_mb')
                            ->numeric()
                            ->step(0.01)
                            ->disabled(),

                        Forms\Components\Select::make('format')
                            ->options([
                                'glb' => 'GLB',
                                'gltf' => 'GLTF',
                                'usdz' => 'USDZ',
                                'obj' => 'OBJ',
                            ])
                            ->default('glb'),
                    ]),

                Forms\Components\Section::make('Совместимость')
                    ->schema([
                        Forms\Components\Toggle::make('ar_compatible')
                            ->default(true),

                        Forms\Components\Toggle::make('vr_compatible')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Статус и теги')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'Загружено' => 'Загружено',
                                'Обрабатывается' => 'Обрабатывается',
                                'Активно' => 'Активно',
                                'В архиве' => 'В архиве',
                            ])
                            ->default('Активно'),

                        Forms\Components\TagsInput::make('tags'),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jewelry.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('material_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Золото' => 'warning',
                        'Платина' => 'slate',
                        'Серебро' => 'gray',
                        'rose_gold' => 'pink',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('format')
                    ->badge(),

                Tables\Columns\IconColumn::make('ar_compatible')
                    ->boolean()
                    ->label('AR'),

                Tables\Columns\IconColumn::make('vr_compatible')
                    ->boolean()
                    ->label('VR'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Активно' => 'success',
                        'Обрабатывается' => 'info',
                        'В архиве' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('material_type')
                    ->options([
                        'Золото' => 'Золото',
                        'Серебро' => 'Серебро',
                        'Платина' => 'Платина',
                        'rose_gold' => 'Розовое золото',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Загружено' => 'Загружено',
                        'Обрабатывается' => 'Обрабатывается',
                        'Активно' => 'Активно',
                        'В архиве' => 'В архиве',
                    ]),

                Tables\Filters\TernaryFilter::make('ar_compatible')
                    ->label('Поддержка AR'),

                Tables\Filters\TernaryFilter::make('vr_compatible')
                    ->label('Поддержка VR'),
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
            'index' => \App\Filament\Tenant\Resources\Jewelry3DModelResource\Pages\ListJewelry3DModels::class,
            'create' => \App\Filament\Tenant\Resources\Jewelry3DModelResource\Pages\CreateJewelry3DModel::class,
            'edit' => \App\Filament\Tenant\Resources\Jewelry3DModelResource\Pages\EditJewelry3DModel::class,
        ];
    }
}
