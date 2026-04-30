<?php

declare(strict_types=1);

namespace App\Domains\CRM\Filament\Resources;

use App\Domains\CRM\Models\CrmPipeline;
use App\Domains\CRM\Models\CrmStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * CrmPipelineResource — Filament ресурс для управления воронками.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmPipelineResource extends Resource
{
    protected static ?string $model = CrmPipeline::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Components\TextInput $component, $state) => $component
                                ->getContainer()
                                ->getComponent('slug')
                                ?->state(Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('vertical')
                            ->options([
                                'hotels' => 'Гостиницы',
                                'beauty' => 'Бьюти-салоны',
                                'flowers' => 'Флористика',
                                'taxi' => 'Такси',
                                'default' => 'Универсальная',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\TextInput::make('color')
                            ->type('color')
                            ->default('#6b7280'),
                        Forms\Components\TextInput::make('icon')
                            ->default('heroicon-o-chart-bar'),
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Воронка по умолчанию'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vertical')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hotels' => 'blue',
                        'beauty' => 'pink',
                        'flowers' => 'yellow',
                        'taxi' => 'orange',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('stages_count')
                    ->label('Этапов')
                    ->counts('stages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->options([
                        'hotels' => 'Гостиницы',
                        'beauty' => 'Бьюти-салоны',
                        'flowers' => 'Флористика',
                        'taxi' => 'Такси',
                        'default' => 'Универсальная',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('По умолчанию'),
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

    public static function getRelations(): array
    {
        return [
            'stages' => Tables\Columns\TextColumn::make('stages.name'),
        ];
    }
}
