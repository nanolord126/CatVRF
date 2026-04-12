<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirectResource;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FarmDirectResource extends Resource
{

    protected static ?string $model = Farm::class;

        protected static ?string $navigationIcon = 'heroicon-o-document';

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

                    BadgeColumn::make('status')
                        ->label('Статус')
                        ->colors([
                            'gray' => 'draft',
                            'success' => 'published',
                            'danger' => 'archived',
                        ])
                        ->icons([
                            'heroicon-m-pencil' => 'draft',
                            'heroicon-m-check' => 'published',
                            'heroicon-m-archive-box' => 'archived',
                        ])
                        ->sortable(),

                    BadgeColumn::make('is_active')
                        ->label('Активно')
                        ->colors([
                            'success' => true,
                            'gray' => false,
                        ])
                        ->icons([
                            'heroicon-m-check-circle' => true,
                            'heroicon-m-x-circle' => false,
                        ]),

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
                'index' => Pages\ListFarmDirectResource::route('/'),
                'create' => Pages\CreateFarmDirectResource::route('/create'),
                'edit' => Pages\EditFarmDirectResource::route('/{record}/edit'),
                'view' => Pages\ViewFarmDirectResource::route('/{record}'),
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
