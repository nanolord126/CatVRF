<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\BehaviorRating;
use Modules\VetGrooming\Infrastructure\Models\GroomingSessionModel;

class GroomingSessionResource extends Resource
{
    protected static ?string $model = GroomingSessionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationGroup = 'Груминг';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('pet_id')
                            ->label('Питомец')
                            ->relationship('pet', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('service_type')
                            ->label('Тип услуги')
                            ->options(GroomingServiceType::class)
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('groomer_id')
                            ->label('Грумер')
                            ->relationship('groomer', 'full_name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('clinic_id')
                            ->label('Клиника')
                            ->relationship('clinic', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Начало сеанса'),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Окончание сеанса'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Поведение и заметки')
                    ->schema([
                        Forms\Components\Select::make('behavior_rating')
                            ->label('Оценка поведения')
                            ->options(BehaviorRating::class)
                            ->required(fn ($get) => $get('status') === GroomingStatus::COMPLETED->value),

                        Forms\Components\Textarea::make('behavior_notes')
                            ->label('Заметки о поведении')
                            ->rows(3),

                        Forms\Components\Textarea::make('allergy_alerts')
                            ->label('Предупреждения об аллергиях')
                            ->rows(3)
                            ->disabled()
                            ->helperText('Автоматически подтягивается из медицинской карты'),

                        Forms\Components\Textarea::make('medical_notes')
                            ->label('Медицинские заметки')
                            ->rows(3)
                            ->helperText('Состояние кожи, чувствительные области и т.д.'),
                    ]),

                Forms\Components\Section::make('Использованные продукты')
                    ->schema([
                        Forms\Components\KeyValue::make('products_used')
                            ->label('Продукты')
                            ->keyLabel('Продукт')
                            ->valueLabel('Количество/Примечание'),
                    ]),

                Forms\Components\Section::make('Фотографии')
                    ->schema([
                        Forms\Components\TagsInput::make('before_photos')
                            ->label('Фото "До"')
                            ->placeholder('URL или путь к фото'),

                        Forms\Components\TagsInput::make('after_photos')
                            ->label('Фото "После"')
                            ->placeholder('URL или путь к фото'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(GroomingStatus::class)
                            ->default(GroomingStatus::SCHEDULED->value)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pet.name')
                    ->label('Питомец')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Тип услуги')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        GroomingServiceType::FULL_GROOMING->value => 'primary',
                        GroomingServiceType::SPA->value => 'success',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('groomer.full_name')
                    ->label('Грумер')
                    ->searchable()
                    ->toggleable(),

                ViewColumn::make('status')
                    ->label('Статус')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            GroomingStatus::SCHEDULED->value => 'Запланирован',
                            GroomingStatus::IN_PROGRESS->value => 'В процессе',
                            GroomingStatus::COMPLETED->value => 'Завершён',
                            GroomingStatus::CANCELLED->value => 'Отменён',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            GroomingStatus::SCHEDULED->value => 'info',
                            GroomingStatus::IN_PROGRESS->value => 'warning',
                            GroomingStatus::COMPLETED->value => 'success',
                            GroomingStatus::CANCELLED->value => 'danger',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            GroomingStatus::SCHEDULED->value => 'calendar',
                            GroomingStatus::IN_PROGRESS->value => 'scissors',
                            GroomingStatus::COMPLETED->value => 'check-circle',
                            GroomingStatus::CANCELLED->value => 'x-circle',
                            default => null,
                        },
                    ]),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность (мин)')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('behavior_rating')
                    ->label('Поведение')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        BehaviorRating::EXCELLENT->value => 'success',
                        BehaviorRating::GOOD->value => 'info',
                        BehaviorRating::FAIR->value => 'warning',
                        BehaviorRating::POOR->value => 'orange',
                        BehaviorRating::AGGRESSIVE->value => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('has_allergy_alerts')
                    ->label('Аллергии')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(GroomingStatus::class),

                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Тип услуги')
                    ->options(GroomingServiceType::class),

                Tables\Filters\SelectFilter::make('behavior_rating')
                    ->label('Оценка поведения')
                    ->options(BehaviorRating::class),

                Tables\Filters\Filter::make('with_photos')
                    ->label('С фото')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNotNull('before_photos')
                            ->orWhereNotNull('after_photos');
                    })),

                Tables\Filters\Filter::make('aggressive_behavior')
                    ->label('Агрессивное поведение')
                    ->query(fn ($query) => $query->where('behavior_rating', BehaviorRating::AGGRESSIVE->value)),
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
}
