<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\TrainerCertificationModel;

final class TrainerCertificationResource extends Resource
{
    protected static ?string $model = TrainerCertificationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Fitness Analytics';

    protected static ?int $navigationSort = 11;

    protected static ?string $label = 'Сертификаты тренеров';

    protected static ?string $pluralLabel = 'Сертификаты тренеров';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о сертификате')
                    ->schema([
                        Forms\Components\Select::make('trainer_id')
                            ->relationship('trainer', 'full_name')
                            ->required()
                            ->searchable()
                            ->label('Тренер'),
                        Forms\Components\Select::make('certification_type')
                            ->options([
                                'internal' => 'Внутренний',
                                'external' => 'Внешний',
                            ])
                            ->required()
                            ->label('Тип сертификата'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),
                        Forms\Components\TextInput::make('issuer')
                            ->maxLength(255)
                            ->label('Организация'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Срок действия')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->label('Дата выдачи'),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Дата истечения'),
                        Forms\Components\TextInput::make('certificate_number')
                            ->maxLength(255)
                            ->label('Номер сертификата'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Документ')
                    ->schema([
                        Forms\Components\FileUpload::make('document_file')
                            ->directory('certifications')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->label('Загрузить документ'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Активный',
                                'expired' => 'Истёк',
                                'pending_verification' => 'Ожидает проверки',
                                'revoked' => 'Отозван',
                            ])
                            ->required()
                            ->label('Статус'),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Проверен'),
                        Forms\Components\DatePicker::make('verified_at')
                            ->disabled()
                            ->label('Дата проверки'),
                        Forms\Components\Select::make('verified_by')
                            ->relationship('verifiedBy', 'name')
                            ->label('Проверил'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Заметки')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Заметки'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trainer.full_name')
                    ->label('Тренер')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('certification_type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'external',
                    ])
                    ->label('Тип'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                        'warning' => 'pending_verification',
                        'gray' => 'revoked',
                    ])
                    ->label('Статус'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Проверен'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Истекает')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('issuer')
                    ->label('Организация')
                    ->toggleable(),
            ])
            ->defaultSort('expiry_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активный',
                        'expired' => 'Истёк',
                        'pending_verification' => 'Ожидает проверки',
                        'revoked' => 'Отозван',
                    ])
                    ->label('Статус'),
                Tables\Filters\SelectFilter::make('certification_type')
                    ->options([
                        'internal' => 'Внутренний',
                        'external' => 'Внешний',
                    ])
                    ->label('Тип'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()->addDays(30)))
                    ->label('Истекает скоро'),
                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()))
                    ->label('Истёкшие'),
                Tables\Filters\Filter::make('unverified')
                    ->query(fn ($query) => $query->where('is_verified', false))
                    ->label('Не проверенные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-circle')
                    ->label('Проверить')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        \Modules\Fitness\Application\Services\TrainerCertificationService::class;
                        $service = app(\Modules\Fitness\Application\Services\TrainerCertificationService::class);
                        $service->verifyCertification($record->id, auth()->id());
                    })
                    ->visible(fn ($record) => !$record->is_verified),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('verify')
                    ->label('Проверить выбранные')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records) {
                        $service = app(\Modules\Fitness\Application\Services\TrainerCertificationService::class);
                        foreach ($records as $record) {
                            if (!$record->is_verified) {
                                $service->verifyCertification($record->id, auth()->id());
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages\ListTrainerCertifications::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages\CreateTrainerCertification::route('/create'),
            'view' => \Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages\ViewTrainerCertification::route('/{record}'),
            'edit' => \Modules\Fitness\Filament\Resources\TrainerCertificationResource\Pages\EditTrainerCertification::route('/{record}/edit'),
        ];
    }
}
