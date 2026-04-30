<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\TrainerModel;

final class TrainerResource extends Resource
{
    protected static ?string $model = TrainerModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 2;

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
                        Forms\Components\TextInput::make('specialization')
                            ->maxLength(255)
                            ->label('Специализация'),
                    ]),

                Forms\Components\Section::make('Квалификация')
                    ->schema([
                        Forms\Components\TagsInput::make('certifications')
                            ->label('Сертификаты'),
                        Forms\Components\Textarea::make('bio')
                            ->label('О себе')
                            ->rows(3),
                        Forms\Components\FileUpload::make('photo_url')
                            ->image()
                            ->directory('trainers')
                            ->label('Фото'),
                    ]),

                Forms\Components\Section::make('График и тарифы')
                    ->schema([
                        Forms\Components\KeyValue::make('working_hours')
                            ->label('График работы')
                            ->keyLabel('День')
                            ->valueLabel('Часы')
                            ->addable(true)
                            ->deletable(true),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0)
                            ->label('Ставка в час'),
                    ]),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Рейтинг'),
                        Forms\Components\TextInput::make('total_sessions')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Всего сессий'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->default(true)
                            ->label('Доступен для записи'),
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
                Tables\Columns\TextColumn::make('specialization')
                    ->searchable()
                    ->label('Специализация'),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric()
                    ->sortable()
                    ->label('Рейтинг'),
                Tables\Columns\TextColumn::make('total_sessions')
                    ->numeric()
                    ->sortable()
                    ->label('Сессий'),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->money('RUB')
                    ->label('Ставка'),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Доступен'),
            ])
            ->defaultSort('rating', 'desc')
            ->filters([
                Tables\Filters\Filter::make('is_available')
                    ->query(fn ($query) => $query->where('is_available', true))
                    ->label('Только доступные'),
                Tables\Filters\Filter::make('is_on_hold')
                    ->query(fn ($query) => $query->where('is_on_hold', true))
                    ->label('На удержании'),
                Tables\Filters\SelectFilter::make('specialization')
                    ->options(fn () => TrainerModel::distinct()->pluck('specialization', 'specialization'))
                    ->label('Специализация'),
                Tables\Filters\SelectFilter::make('qualification_level')
                    ->options([
                        'junior' => 'Junior',
                        'certified' => 'Certified',
                        'senior' => 'Senior',
                        'master' => 'Master',
                        'specialist' => 'Specialist',
                    ])
                    ->label('Квалификация'),
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
            'index' => \Modules\Fitness\Filament\Resources\TrainerResource\Pages\ListTrainers::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\TrainerResource\Pages\CreateTrainer::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\TrainerResource\Pages\EditTrainer::route('/{record}/edit'),
        ];
    }
}
