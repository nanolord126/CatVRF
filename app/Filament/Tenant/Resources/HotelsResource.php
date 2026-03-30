<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelsResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = B2BHotelStorefront::class;

        protected static ?string $navigationIcon = 'heroicon-o-collection';

        protected static ?string $navigationGroup = 'Resources';

        protected static ?int $navigationSort = 0;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Базовые сведения')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Название')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    TextInput::make('slug')
                                        ->label('Идентификатор')
                                        ->unique(ignoreRecord: true)
                                        ->columnSpan(1),

                                    Select::make('status')
                                        ->label('Статус')
                                        ->options([
                                            'draft' => 'Черновик',
                                            'published' => 'Опубликовано',
                                            'archived' => 'Архив',
                                        ])
                                        ->default('draft')
                                        ->columnSpan(1),
                                ]),
                        ]),

                    Section::make('Описание')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            Textarea::make('description')
                                ->label('Описание')
                                ->maxLength(1000)
                                ->rows(4),

                            RichEditor::make('content')
                                ->label('Содержимое')
                                ->columnSpan('full')
                                ->maxLength(5000),
                        ]),

                    Section::make('Медиа')
                        ->icon('heroicon-m-photo')
                        ->collapsed()
                        ->schema([
                            FileUpload::make('image')
                                ->label('Изображение')
                                ->image()
                                ->directory('resources'),

                            FileUpload::make('attachments')
                                ->label('Файлы')
                                ->multiple()
                                ->directory('attachments')
                                ->columnSpan('full'),
                        ]),

                    Section::make('Настройки')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->collapsed()
                        ->columns(2)
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            Toggle::make('is_featured')
                                ->label('Избранное')
                                ->default(false),

                            TextInput::make('priority')
                                ->label('Приоритет')
                                ->numeric()
                                ->default(0),

                            DatePicker::make('published_at')
                                ->label('Дата публикации'),

                            TagsInput::make('tags')
                                ->label('Теги')
                                ->columnSpan('full'),
                        ]),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListHotels::route('/'),
                'create' => Pages\\CreateHotels::route('/create'),
                'edit' => Pages\\EditHotels::route('/{record}/edit'),
                'view' => Pages\\ViewHotels::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListHotels::route('/'),
                'create' => Pages\\CreateHotels::route('/create'),
                'edit' => Pages\\EditHotels::route('/{record}/edit'),
                'view' => Pages\\ViewHotels::route('/{record}'),
            ];
        }
}
