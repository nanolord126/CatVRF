<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance;

use App\Domains\Freelance\Models\FreelanceServiceOffer;
use App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

/**
 * КАНОН 2026 — FREELANCE SERVICE OFFER RESOURCE
 * Управление услугами фрилансеров: кворки, фиксированные услуги.
 */
final class FreelanceServiceOfferResource extends Resource
{
    protected static ?string $model = FreelanceServiceOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Фриланс Биржа';

    protected static ?string $label = 'Услуга (Offer)';

    protected static ?string $pluralLabel = 'Услуги';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Параметры услуги')
                    ->columns(2)
                    ->schema([
                        Select::make('freelancer_id')
                            ->label('Специалист')
                            ->relationship('freelancer', 'full_name')
                            ->searchable()
                            ->required(),

                        TextInput::make('title')
                            ->label('Название услуги')
                            ->placeholder('Создание Landing Page на Laravel')
                            ->required(),

                        TextInput::make('price_kopecks')
                            ->label('Стоимость (коп.)')
                            ->numeric()
                            ->required()
                            ->prefix('₽'),

                        TextInput::make('delivery_time_days')
                            ->label('Срок выполнения (дней)')
                            ->numeric()
                            ->required()
                            ->suffix('дней'),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ]),

                Section::make('Описание услуги')
                    ->schema([
                        RichEditor::make('description')
                            ->label('Что входит в стоимость')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Услуга')
                    ->searchable()
                    ->description(fn (FreelanceServiceOffer $record) => $record->freelancer->full_name),

                TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->money('RUB', divisor: 100)
                    ->sortable(),

                TextColumn::make('delivery_time_days')
                    ->label('Срок')
                    ->suffix(' дн.')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
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
            'index' => Pages\ListFreelanceServiceOffers::route('/'),
            'create' => Pages\CreateFreelanceServiceOffer::route('/create'),
            'edit' => Pages\EditFreelanceServiceOffer::route('/{record}/edit'),
        ];
    }
}
