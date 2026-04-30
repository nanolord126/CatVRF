<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ExoticCertificationModel;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

final class ExoticCertificationResource extends Resource
{
    protected static ?string $model = ExoticCertificationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Сертификация')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'name')
                            ->required()
                            ->searchable()
                            ->label('Грумер'),
                        
                        Forms\Components\Select::make('exotic_category')
                            ->options([
                                'birds' => 'Птицы',
                                'reptiles' => 'Рептилии',
                                'small_mammals' => 'Мелкие млекопитающие',
                                'large_mammals' => 'Крупные млекопитающие',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('subcategory', null))
                            ->label('Категория'),

                        Forms\Components\Select::make('subcategory')
                            ->options(function (callable $get) {
                                $category = $get('exotic_category');
                                return match ($category) {
                                    'birds' => [
                                        'large_parrots' => 'Крупные попугаи',
                                        'medium_parrots' => 'Средние попугаи',
                                        'small_birds' => 'Мелкие птицы',
                                    ],
                                    'reptiles' => [
                                        'iguanas' => 'Игуаны',
                                        'lizards' => 'Ящерицы',
                                        'turtles' => 'Черепахи',
                                        'snakes' => 'Змеи',
                                    ],
                                    'small_mammals' => [
                                        'ferrets' => 'Хорьки',
                                        'rabbits' => 'Кролики',
                                        'chinchillas' => 'Шиншиллы',
                                        'guinea_pigs' => 'Морские свинки',
                                    ],
                                    'large_mammals' => [
                                        'large_dogs' => 'Крупные собаки',
                                        'giant_dogs' => 'Гигантские породы',
                                        'large_cats' => 'Крупные кошки',
                                    ],
                                    default => [],
                                };
                            })
                            ->label('Подкатегория'),

                        Forms\Components\Select::make('exotic_group')
                            ->options([
                                'group_a' => 'Группа A (Высокий риск)',
                                'group_b' => 'Группа B (Средний риск)',
                                'group_c' => 'Группа C (Базовый уровень)',
                            ])
                            ->required()
                            ->label('Группа риска'),

                        Forms\Components\Select::make('certification_level')
                            ->options([
                                'certified' => 'Certified Exotic Groomer',
                                'advanced' => 'Advanced Exotic Groomer',
                                'master' => 'Master Exotic Groomer',
                            ])
                            ->required()
                            ->label('Уровень сертификации'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Детали')
                    ->schema([
                        Forms\Components\FileUpload::make('practical_exam_video')
                            ->label('Видео практического экзамена')
                            ->acceptedFileTypes(['video/*'])
                            ->maxSize(102400)
                            ->directory('exotic-certifications/videos'),

                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now())
                            ->label('Дата выдачи'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->default(now()->addYear())
                            ->label('Дата истечения'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Активна',
                                'expired' => 'Истекла',
                                'revoked' => 'Отозвана',
                                'suspended' => 'Приостановлена',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Статус'),

                        Forms\Components\TextInput::make('certificate_number')
                            ->default('EXO-' . strtoupper(bin2hex(random_bytes(8))))
                            ->unique()
                            ->label('Номер сертификата'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.name')
                    ->label('Грумер')
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

                Tables\Columns\TextColumn::make('certification_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'certified' => 'success',
                        'advanced' => 'warning',
                        'master' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'certified' => 'Certified',
                        'advanced' => 'Advanced',
                        'master' => 'Master',
                        default => $state,
                    })
                    ->label('Уровень'),

                Tables\Columns\TextColumn::make('exotic_group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'group_a' => 'danger',
                        'group_b' => 'warning',
                        'group_c' => 'success',
                        default => 'gray',
                    })
                    ->label('Группа'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'revoked' => 'gray',
                        'suspended' => 'warning',
                        default => 'gray',
                    })
                    ->label('Статус'),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->label('Истекает'),

                Tables\Columns\IconColumn::make('is_valid')
                    ->boolean()
                    ->label('Валидна')
                    ->getStateUsing(fn ($record): bool => $record->status === 'active' && $record->expiry_date->isFuture()),
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

                Tables\Filters\SelectFilter::make('certification_level')
                    ->options([
                        'certified' => 'Certified',
                        'advanced' => 'Advanced',
                        'master' => 'Master',
                    ])
                    ->label('Уровень'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активна',
                        'expired' => 'Истекла',
                        'revoked' => 'Отозвана',
                        'suspended' => 'Приостановлена',
                    ])
                    ->label('Статус'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()->addDays(30))->where('expiry_date', '>', now()))
                    ->label('Истекает в течение 30 дней'),

                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->where('expiry_date', '<', now()))
                    ->label('Истёкшие'),
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
            'index' => \Modules\VetGrooming\Filament\Resources\ExoticCertificationResource\Pages\ListExoticCertifications::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\ExoticCertificationResource\Pages\CreateExoticCertification::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\ExoticCertificationResource\Pages\EditExoticCertification::route('/{record}/edit'),
        ];
    }
}
