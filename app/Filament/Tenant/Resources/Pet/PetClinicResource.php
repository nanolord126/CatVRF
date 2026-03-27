<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet;

use App\Domains\Pet\Models\PetClinic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

/**
 * КАНЬОН 2026 — FILAMENT RESOURCE ДЛЯ КЛИНИК/САЛОНОВ
 * 
 * Включает tenant scoping, glassmorphism UI и полную валидацию.
 */
final class PetClinicResource extends Resource
{
    protected static ?string $model = PetClinic::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Pet & Veterinary';
    protected static ?string $modelLabel = 'Ветеринарная клиника / Салон';
    protected static ?string $pluralModelLabel = 'Клиники и Салоны';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Данные о заведении и его специализации')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Тип заведения')
                            ->options([
                                'clinic' => 'Ветеринарная клиника',
                                'grooming' => 'Груминг-салон',
                                'pharmacy' => 'Ветаптека',
                                'boarding' => 'Зоогостиница',
                            ])
                            ->required(),
                        TextInput::make('address')
                            ->label('Адрес')
                            ->required(),
                        Forms\Components\KeyValue::make('schedule_json')
                            ->label('Расписание работы')
                            ->keyLabel('День')
                            ->valueLabel('Часы (напр. 09:00-21:00)'),
                    ])->columns(2),

                Section::make('Статус и Рейтинг')
                    ->schema([
                        Toggle::make('is_verified')
                            ->label('Верифицировано платформой')
                            ->default(false),
                        Toggle::make('is_emergency_open')
                            ->label('Работает 24/7 / Экстренная помощь')
                            ->default(false),
                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TagsInput::make('tags')
                            ->label('Теги (для поиска)')
                            ->placeholder('Добавить тег...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clinic' => 'danger',
                        'grooming' => 'success',
                        'pharmacy' => 'info',
                        'boarding' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_verified')
                    ->label('Верификация')
                    ->boolean(),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип заведения')
                    ->options([
                        'clinic' => 'Клиника',
                        'grooming' => 'Груминг',
                        'pharmacy' => 'Аптека',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPetClinics::route('/'),
            'create' => Pages\CreatePetClinic::route('/create'),
            'edit' => Pages\EditPetClinic::route('/{record}/edit'),
        ];
    }
}
                Forms\Components\TextInput::make('phone')->tel()->required(),
                Forms\Components\TextInput::make('rating')->numeric(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('address')->sortable(),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\TextColumn::make('rating')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Tenant\Resources\Pet\PetClinicResource\Pages\ListPetClinics::route('/')];
    }
}
