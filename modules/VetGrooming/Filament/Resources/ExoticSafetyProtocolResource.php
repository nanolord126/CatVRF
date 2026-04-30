<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ExoticSafetyProtocolModel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;

final class ExoticSafetyProtocolResource extends Resource
{
    protected static ?string $model = ExoticSafetyProtocolModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о протоколе')
                    ->schema([
                        Forms\Components\Select::make('exotic_category')
                            ->options([
                                'birds' => 'Птицы',
                                'reptiles' => 'Рептилии',
                                'small_mammals' => 'Мелкие млекопитающие',
                                'large_mammals' => 'Крупные млекопитающие',
                            ])
                            ->required()
                            ->reactive()
                            ->label('Категория'),

                        Forms\Components\TextInput::make('species')
                            ->required()
                            ->placeholder('Например: large_parrots, ferret, etc.')
                            ->label('Вид/Группа'),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Название протокола'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Чек-лист безопасности')
                    ->schema([
                        Forms\Components\Repeater::make('checklist_items')
                            ->schema([
                                Forms\Components\TextInput::make('item')
                                    ->required()
                                    ->label('Пункт чек-листа'),
                                Forms\Components\Toggle::make('is_required')
                                    ->default(true)
                                    ->label('Обязательный'),
                            ])
                            ->columns(2)
                            ->label('Пункты чек-листа'),
                    ]),

                Forms\Components\Section::make('Специфические требования')
                    ->schema([
                        Forms\Components\Textarea::make('temperature_requirements')
                            ->rows(2)
                            ->label('Требования к температуре'),

                        Forms\Components\Textarea::make('handling_requirements')
                            ->rows(2)
                            ->label('Требования к фиксации'),

                        Forms\Components\Textarea::make('safety_precautions')
                            ->rows(3)
                            ->label('Меры предосторожности'),

                        Forms\Components\Textarea::make('prohibited_actions')
                            ->rows(2)
                            ->label('Запрещённые действия'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Риски и уведомления')
                    ->schema([
                        Forms\Components\Textarea::make('specific_risks')
                            ->rows(3)
                            ->label('Специфические риски'),

                        Forms\Components\Toggle::make('requires_veterinary_notification')
                            ->default(false)
                            ->label('Требуется уведомление ветеринара'),

                        Forms\Components\Toggle::make('requires_owner_notification')
                            ->default(false)
                            ->label('Требуется уведомление владельца'),

                        Forms\Components\Textarea::make('post_procedure_recommendations')
                            ->rows(3)
                            ->label('Рекомендации после процедуры'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('exotic_category')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                        'small_mammals' => 'Мелкие млекопитающие',
                        'large_mammals' => 'Крупные млекопитающие',
                        default => $state,
                    })
                    ->label('Категория'),

                Tables\Columns\TextColumn::make('species')
                    ->label('Вид/Группа')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),

                Tables\Columns\TextColumn::make('checklist_items')
                    ->formatStateUsing(fn ($state): int => is_array($state) ? count($state) : 0)
                    ->label('Пунктов чек-листа'),

                Tables\Columns\IconColumn::make('requires_veterinary_notification')
                    ->boolean()
                    ->label('Увед. ветеринара'),

                Tables\Columns\IconColumn::make('requires_owner_notification')
                    ->boolean()
                    ->label('Увед. владельца'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exotic_category')
                    ->options([
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                        'small_mammals' => 'Мелкие млекопитающие',
                        'large_mammals' => 'Крупные млекопитающие',
                    ])
                    ->label('Категория'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),

                Tables\Filters\TernaryFilter::make('requires_veterinary_notification')
                    ->label('Увед. ветеринара'),
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
            'index' => \Modules\VetGrooming\Filament\Resources\ExoticSafetyProtocolResource\Pages\ListExoticSafetyProtocols::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\ExoticSafetyProtocolResource\Pages\CreateExoticSafetyProtocol::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\ExoticSafetyProtocolResource\Pages\EditExoticSafetyProtocol::route('/{record}/edit'),
        ];
    }
}
