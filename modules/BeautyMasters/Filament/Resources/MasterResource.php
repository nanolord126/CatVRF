<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;

final class MasterResource extends Resource
{
    protected static ?string $model = MasterModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Мастера';

    protected static ?string $modelLabel = 'Мастер';

    protected static ?string $pluralModelLabel = 'Мастера';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Салон')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('patronymic')
                            ->label('Отчество')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Детали')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Аватар')
                            ->image()
                            ->directory('masters/avatars')
                            ->maxSize(5120),

                        Forms\Components\Textarea::make('bio')
                            ->label('Биография')
                            ->rows(3),

                        Forms\Components\TagsInput::make('specializations')
                            ->label('Специализации')
                            ->suggestions([
                                'Стрижка',
                                'Маникюр',
                                'Педикюр',
                                'Макияж',
                                'Брови',
                                'Ресницы',
                                'Укладка',
                                'Окрашивание',
                            ]),

                        Forms\Components\TagsInput::make('certifications')
                            ->label('Сертификаты'),

                        Forms\Components\TextInput::make('experience_years')
                            ->label('Опыт (лет)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        Forms\Components\Toggle::make('is_mobile_master')
                            ->label('Мастер на выезд')
                            ->default(false),

                        Forms\Components\TextInput::make('base_commission_rate')
                            ->label('Базовая комиссия (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(30.0)
                            ->minValue(0)
                            ->maxValue(100),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Аватар')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->sortable()
                    ->searchable(['first_name', 'last_name', 'patronymic']),

                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Салон')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Отзывы')
                    ->sortable(),

                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Опыт')
                    ->sortable()
                    ->suffix(' лет'),

                Tables\Columns\ToggleColumn::make('is_mobile_master')
                    ->label('Выезд'),
            ])
            ->defaultSort('last_name')
            ->filters([
                Tables\Filters\SelectFilter::make('venue_id')
                    ->label('Салон')
                    ->relationship('venue', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),

                Tables\Filters\TernaryFilter::make('is_mobile_master')
                    ->label('Выезд')
                    ->placeholder('Все')
                    ->trueLabel('На выезд')
                    ->falseLabel('В салоне'),
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
            'index' => Pages\ListMasters::route('/'),
            'create' => Pages\CreateMaster::route('/create'),
            'edit' => Pages\EditMaster::route('/{record}/edit'),
        ];
    }
}
