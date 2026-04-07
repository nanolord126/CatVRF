<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class MasterResource extends Resource
{
    protected static ?string $model = BeautyMaster::class;

    protected static ?string $navigationIcon   = 'heroicon-o-user';
    protected static ?string $navigationGroup  = 'Beauty';
    protected static ?string $navigationLabel  = 'Мастера';
    protected static ?string $modelLabel       = 'Мастер';
    protected static ?string $pluralModelLabel = 'Мастера';
    protected static ?int    $navigationSort   = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('uuid')
                ->default(fn () => Str::uuid()->toString()),
            Hidden::make('correlation_id')
                ->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Select::make('salon_id')
                        ->label('Салон')
                        ->options(function () {
                            return BeautySalon::query()
                                ->where('tenant_id', filament()->getTenant()?->id)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->columnSpan(2),
                    TextInput::make('name')
                        ->label('ФИО мастера')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('specialization')
                        ->label('Специализация')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Например: парикмахер, мастер маникюра'),
                    TextInput::make('experience_years')
                        ->label('Опыт работы (лет)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->default(0),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                ]),

            Section::make('Настройки')
                ->columns(2)
                ->schema([
                    TextInput::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1)
                        ->default(0),
                    TextInput::make('review_count')
                        ->label('Количество отзывов')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable(),
                TextColumn::make('specialization')
                    ->label('Специализация')
                    ->searchable(),
                TextColumn::make('experience_years')
                    ->label('Опыт (лет)')
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    }),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('salon_id')
                    ->label('Салон')
                    ->options(function () {
                        return BeautySalon::query()
                            ->where('tenant_id', filament()->getTenant()?->id)
                            ->pluck('name', 'id');
                    }),
                TernaryFilter::make('is_active')->label('Активность'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', function (Builder $query) {
                $query->where('tenant_id', filament()->getTenant()?->id);
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMasters::route('/'),
            'create' => Pages\CreateMaster::route('/create'),
            'edit'   => Pages\EditMaster::route('/{record}/edit'),
        ];
    }
}
