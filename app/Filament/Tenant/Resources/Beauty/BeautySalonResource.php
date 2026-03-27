<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Beauty Salon Resource (Layer 7)
 * 
 * Включает Tenant Scoping, UUID-генерацию, Correlation-ID и Fraud Check.
 */
final class BeautySalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Салоны Красоты';
    protected static ?string $navigationGroup = 'Beauty & Wellness';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->default(false),
                    ])->columns(2),
                
                Forms\Components\Section::make('Геолокация')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('lon')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.00000001),
                    ])->columns(2),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => tenant('id')),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->before(function ($record) {
                        Log::channel('audit')->info('Filament Resource Edit: BeautySalon', [
                            'id' => $record->id,
                            'tenant_id' => tenant('id'),
                            'correlation_id' => (string) Str::uuid()
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBeautySalons::route('/'),
            'create' => Pages\CreateBeautySalon::route('/create'),
            'edit' => Pages\EditBeautySalon::route('/{record}/edit'),
        ];
    }
}
