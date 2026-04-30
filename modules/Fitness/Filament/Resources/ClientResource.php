<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\ClientModel;

final class ClientResource extends Resource
{
    protected static ?string $model = ClientModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Персональная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->label('Пользователь'),
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Имя'),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Фамилия'),
                        Forms\Components\TextInput::make('patronymic')
                            ->maxLength(255)
                            ->label('Отчество'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Телефон'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->label('Email'),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Дата рождения'),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Мужской',
                                'female' => 'Женский',
                            ])
                            ->label('Пол'),
                    ]),

                Forms\Components\Section::make('Медицинская информация')
                    ->schema([
                        Forms\Components\TagsInput::make('medical_restrictions')
                            ->label('Медицинские ограничения'),
                        Forms\Components\TextInput::make('emergency_contact')
                            ->maxLength(255)
                            ->label('Контактное лицо (ЧС)'),
                        Forms\Components\TextInput::make('emergency_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Телефон (ЧС)'),
                    ]),

                Forms\Components\Section::make('Цели и уровень')
                    ->schema([
                        Forms\Components\TagsInput::make('goals')
                            ->label('Цели')
                            ->suggestions([
                                'Похудение',
                                'Набор массы',
                                'Улучшение выносливости',
                                'Развитие силы',
                                'Гибкость',
                            ]),
                        Forms\Components\Select::make('fitness_level')
                            ->options([
                                'beginner' => 'Начинающий',
                                'intermediate' => 'Средний',
                                'advanced' => 'Продвинутый',
                            ])
                            ->default('beginner')
                            ->label('Уровень подготовки'),
                    ]),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\TextInput::make('loyalty_points')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->suffix(' баллов')
                            ->label('Баллы лояльности'),
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Всего посещений'),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\FileUpload::make('photo_url')
                            ->image()
                            ->directory('clients')
                            ->label('Фото'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->label('Фамилия'),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->label('Имя'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Телефон'),
                Tables\Columns\TextColumn::make('fitness_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'gray',
                        'intermediate' => 'warning',
                        'advanced' => 'success',
                    })
                    ->label('Уровень'),
                Tables\Columns\TextColumn::make('total_visits')
                    ->numeric()
                    ->sortable()
                    ->label('Посещений'),
                Tables\Columns\TextColumn::make('loyalty_points')
                    ->numeric()
                    ->sortable()
                    ->suffix(' баллов')
                    ->label('Баллы'),
            ])
            ->defaultSort('total_visits', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('fitness_level')
                    ->options([
                        'beginner' => 'Начинающий',
                        'intermediate' => 'Средний',
                        'advanced' => 'Продвинутый',
                    ])
                    ->label('Уровень подготовки'),
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
            'index' => \Modules\Fitness\Filament\Resources\ClientResource\Pages\ListClients::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\ClientResource\Pages\CreateClient::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\ClientResource\Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
