<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\VerificationLogResource\Pages;
use App\Models\VerificationLog;
use App\Enums\VerificationType;
use App\Enums\VerificationResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class VerificationLogResource extends Resource
{
    protected static ?string $model = VerificationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Логи верификации';

    protected static ?string $modelLabel = 'Лог верификации';

    protected static ?string $pluralModelLabel = 'Логи верификации';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о верификации')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(VerificationType::class)
                            ->required()
                            ->label('Тип верификации'),

                        Forms\Components\Select::make('result')
                            ->options(VerificationResult::class)
                            ->required()
                            ->label('Результат'),

                        Forms\Components\TextInput::make('provider')
                            ->required()
                            ->label('Провайдер'),

                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->label('Оценка'),

                        Forms\Components\Textarea::make('reason')
                            ->label('Причина')
                            ->rows(3),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Метаданные'),

                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Correlation ID'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Связанные сущности')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->label('Пользователь'),

                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->label('Тенант'),

                        Forms\Components\Select::make('business_group_id')
                            ->relationship('businessGroup', 'name')
                            ->searchable()
                            ->label('Бизнес-группа'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (VerificationType $state): string => $state->label())
                    ->badge()
                    ->color(fn (VerificationType $state): string => match ($state) {
                        VerificationType::FioPhoto => 'primary',
                        VerificationType::Inn => 'success',
                        VerificationType::Document => 'warning',
                        VerificationType::Manual => 'gray',
                        VerificationType::Passkey => 'info',
                    }),

                Tables\Columns\TextColumn::make('provider')
                    ->label('Провайдер')
                    ->searchable(),

                Tables\Columns\TextColumn::make('result')
                    ->label('Результат')
                    ->formatStateUsing(fn (VerificationResult $state): string => $state->label())
                    ->badge()
                    ->color(fn (VerificationResult $state): string => match ($state) {
                        VerificationResult::Success => 'success',
                        VerificationResult::Failed => 'danger',
                        VerificationResult::Pending => 'warning',
                        VerificationResult::RequiresReview => 'info',
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Оценка')
                    ->formatStateUsing(fn (float $state): string => number_format($state * 100, 1).'%'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Тенант')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(VerificationType::class)
                    ->label('Тип верификации'),

                Tables\Filters\SelectFilter::make('result')
                    ->options(VerificationResult::class)
                    ->label('Результат'),

                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'dadata' => 'DaData',
                        'faceio' => 'FACEIO',
                        'aws_rekognition' => 'AWS Rekognition',
                        'yandex_vision' => 'Yandex Vision',
                        'tesseract' => 'Tesseract',
                        'aws_textract' => 'AWS Textract',
                        'mock' => 'Mock',
                    ])
                    ->label('Провайдер'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Add bulk actions if needed
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'user',
            'tenant',
            'businessGroup',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerificationLogs::route('/'),
            'view' => Pages\ViewVerificationLog::route('/{record}'),
        ];
    }
}
