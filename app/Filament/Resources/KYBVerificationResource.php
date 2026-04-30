<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\KYBVerificationResource\Pages;
use App\Models\KYBVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class KYBVerificationResource extends Resource
{
    protected static ?string $model = KYBVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Compliance';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->required()
                            ->length(10),
                        Forms\Components\Select::make('verification_status')
                            ->label('Статус верификации')
                            ->options([
                                'pending' => 'Ожидает',
                                'in_progress' => 'В процессе',
                                'approved' => 'Одобрено',
                                'rejected' => 'Отклонено',
                                'requires_review' => 'Требует проверки',
                            ])
                            ->required(),
                        Forms\Components\Select::make('risk_level')
                            ->label('Уровень риска')
                            ->options([
                                'low' => 'Низкий',
                                'medium' => 'Средний',
                                'high' => 'Высокий',
                                'critical' => 'Критический',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('risk_score')
                            ->label('Оценка риска')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])->columns(2),

                Forms\Components\Section::make('Ручной просмотр')
                    ->schema([
                        Forms\Components\Textarea::make('manual_review_notes')
                            ->label('Заметки проверки')
                            ->rows(3),
                        Forms\Components\DateTimePicker::make('manual_reviewed_at')
                            ->label('Проверено в'),
                    ])->visible(fn ($record) => $record?->manual_review_required),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Тенант')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'requires_review',
                    ]),
                Tables\Columns\BadgeColumn::make('risk_level')
                    ->label('Риск')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Оценка')
                    ->sortable(),
                Tables\Columns\IconColumn::make('manual_review_required')
                    ->label('Требует проверки')
                    ->boolean(),
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Верифицировано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'in_progress' => 'В процессе',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                        'requires_review' => 'Требует проверки',
                    ]),
                Tables\Filters\SelectFilter::make('risk_level')
                    ->label('Уровень риска')
                    ->options([
                        'low' => 'Низкий',
                        'medium' => 'Средний',
                        'high' => 'Высокий',
                        'critical' => 'Критический',
                    ]),
                Tables\Filters\Filter::make('manual_review_required')
                    ->label('Требует проверки')
                    ->query(fn ($query) => $query->where('manual_review_required', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (KYBVerification $record) => $record->update([
                        'verification_status' => 'approved',
                        'manual_review_required' => false,
                        'manual_reviewed_at' => CarbonImmutable::now(),
                    ]))
                    ->visible(fn (KYBVerification $record) => $record->manual_review_required),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина')
                            ->required(),
                    ])
                    ->action(function (KYBVerification $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'manual_review_required' => false,
                            'manual_reviewed_at' => CarbonImmutable::now(),
                            'manual_review_notes' => $data['rejection_reason'],
                        ]);
                    })
                    ->visible(fn (KYBVerification $record) => $record->manual_review_required),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve')
                    ->label('Одобрить выбранные')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update([
                        'verification_status' => 'approved',
                        'manual_review_required' => false,
                        'manual_reviewed_at' => CarbonImmutable::now(),
                    ])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'uboChains' => Tables\Columns\TextColumn::make('entity_name'),
            'sanctionsScreenings' => Tables\Columns\TextColumn::make('screening_status'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKYBVerifications::route('/'),
            'create' => Pages\CreateKYBVerification::route('/create'),
            'view' => Pages\ViewKYBVerification::route('/{record}'),
            'edit' => Pages\EditKYBVerification::route('/{record}/edit'),
        ];
    }
}
