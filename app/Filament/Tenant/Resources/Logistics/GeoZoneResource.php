<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use App\Domains\Logistics\Models\GeoZone;
use App\Filament\Tenant\Resources\Logistics\GeoZoneResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * GeoZone Resource (2026 Edition)
 * 
 * Управление районами доставки и зонами работы.
 * Канон 2026: Tenant Scoping, JSON Editor (Boundary).
 */
final class GeoZoneResource extends Resource
{
    protected static ?string $model = GeoZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Logistics';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options([
                                'district' => 'District',
                                'service_area' => 'Service Area',
                                'restricted' => 'Restricted Area',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Geospatial Boundary')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('boundary')
                            ->label('Boundary Points (GeoJSON Area or Point Array)')
                            ->required()
                            ->helperText('In 2026, use the Map widget during the next phase.'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'district',
                        'success' => 'service_area',
                        'danger' => 'restricted',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeoZones::route('/'),
            'create' => Pages\CreateGeoZone::route('/create'),
            'edit' => Pages\EditGeoZone::route('/{record}/edit'),
        ];
    }
}
