<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Jewelry3DModelResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListJewelry3DModel::route('/'),
                'create' => Pages\\CreateJewelry3DModel::route('/create'),
                'edit' => Pages\\EditJewelry3DModel::route('/{record}/edit'),
                'view' => Pages\\ViewJewelry3DModel::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListJewelry3DModel::route('/'),
                'create' => Pages\\CreateJewelry3DModel::route('/create'),
                'edit' => Pages\\EditJewelry3DModel::route('/{record}/edit'),
                'view' => Pages\\ViewJewelry3DModel::route('/{record}'),
            ];
        }
}
