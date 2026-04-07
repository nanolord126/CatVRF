<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\FlowerOrder;
use Filament\Forms;
use Filament\Forms\Components\{DatePicker, FileUpload, RichEditor, Section, Select, TagsInput, Textarea, TextInput, Toggle};
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction, EditAction, ViewAction};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FlowerOrderResource extends Resource
{
    protected static ?string $model = FlowerOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

        protected static ?string $navigationGroup = 'Flowers';

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
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable()
                        ->limit(50),

                    TextColumn::make('slug')
                        ->label('Slug')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'published' => 'success',
                            'archived' => 'danger',
                            default => 'gray',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'draft' => 'heroicon-m-pencil',
                            'published' => 'heroicon-m-check',
                            'archived' => 'heroicon-m-archive-box',
                            default => 'heroicon-m-question-mark-circle',
                        })
                        ->sortable(),

                    TextColumn::make('is_active')
                        ->label('Активно')
                        ->badge()
                        ->color(fn ($state): string => $state ? 'success' : 'gray')
                        ->icon(fn ($state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),

                    TextColumn::make('priority')
                        ->label('Приоритет')
                        ->numeric()
                        ->sortable(),

                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime('d.m.Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->label('Обновлено')
                        ->dateTime('d.m.Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'draft' => 'Черновик',
                            'published' => 'Опубликовано',
                            'archived' => 'Архив',
                        ]),

                    Filter::make('is_active')
                        ->label('Только активные')
                        ->query(fn (Builder $q) => $q->where('is_active', true)),

                    Filter::make('is_featured')
                        ->label('Только избранные')
                        ->query(fn (Builder $q) => $q->where('is_featured', true)),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ])
                ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFlowerOrderResource::route('/'),
                'create' => Pages\CreateFlowerOrderResource::route('/create'),
                'edit' => Pages\EditFlowerOrderResource::route('/{record}/edit'),
                'view' => Pages\ViewFlowerOrderResource::route('/{record}'),
            ];
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id ?? 0);
        }
}
