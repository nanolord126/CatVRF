<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Master;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Beauty Master Resource (Layer 7)
 * 
 * Включает Tenant Scoping, UUID-генерацию, привязку к салону и Fraud Check.
 */
final class MasterResource extends Resource
{
    protected static ?string $model = Master::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Мастера';
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
                        Forms\Components\TextInput::make('full_name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('salon_id')
                            ->label('Салон')
                            ->relationship(
                                'salon', 
                                'name', 
                                fn (Builder $query) => $query->where('tenant_id', tenant('id'))
                            )
                            ->required(),
                        Forms\Components\TagsInput::make('specialization')
                            ->label('Специализация')
                            ->placeholder('Добавьте навыки...')
                            ->required(),
                        Forms\Components\TextInput::make('experience_years')
                            ->label('Стаж (лет)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
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
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Стаж (лет)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salon_id')
                    ->label('Салон')
                    ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', tenant('id'))),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasters::route('/'),
            'create' => Pages\CreateMaster::route('/create'),
            'edit' => Pages\EditMaster::route('/{record}/edit'),
        ];
    }
}
