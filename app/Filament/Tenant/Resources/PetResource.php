<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Pet\Models\B2BPetOrder;
use Illuminate\Database\Eloquent\Builder;

/**
 * PetResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetResource extends Resource
{
    protected static ?string $model = \App\Domains\Veterinary\Models\Pet::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    
    protected static ?string $navigationGroup = 'Veterinary Services';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Pet Passport')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('General Information')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('species')
                                        ->options([
                                            'dog' => 'Dog',
                                            'cat' => 'Cat',
                                            'bird' => 'Bird',
                                            'rabbit' => 'Rabbit',
                                            'other' => 'Other',
                                        ])
                                        ->required(),
                                    Forms\Components\TextInput::make('breed')
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('birth_date')
                                        ->required(),
                                    Forms\Components\Select::make('gender')
                                        ->options([
                                            'male' => 'Male',
                                            'female' => 'Female',
                                        ])
                                        ->required(),
                                    Forms\Components\TextInput::make('weight')
                                        ->numeric()
                                        ->suffix('kg'),
                                    Forms\Components\Toggle::make('is_neutered')
                                        ->label('Neutered/Spayed'),
                                    Forms\Components\TextInput::make('uuid')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->label('Internal ID (UUID)'),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Official Passport')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            Forms\Components\Section::make('Chipping & Registration')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('chip_number')
                                                ->label('Microchip Number')
                                                ->placeholder('900...'),
                                            Forms\Components\DatePicker::make('chip_installed_at')
                                                ->label('Date of Installation'),
                                            Forms\Components\TextInput::make('passport_number')
                                                ->label('Passport Series/Number')
                                                ->placeholder('RU-VET-000...'),
                                        ]),
                                ]),
                            Forms\Components\Section::make('Pedigree')
                                ->schema([
                                    Forms\Components\Repeater::make('pedigree')
                                        ->relationship('pedigree')
                                        ->schema([
                                            Forms\Components\TextInput::make('title')
                                                ->label('Relation (Father/Mother)')
                                                ->required(),
                                            Forms\Components\TextInput::make('parent_name')
                                                ->required(),
                                            Forms\Components\TextInput::make('registration_number')
                                                ->label('Reg Number'),
                                        ])
                                        ->columns(3)
                                        ->collapsible(),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Vaccination Log')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Forms\Components\Repeater::make('vaccinations')
                                ->relationship('vaccinations')
                                ->schema([
                                    Forms\Components\TextInput::make('vaccine_name')
                                        ->required(),
                                    Forms\Components\TextInput::make('batch_number')
                                        ->label('Batch #'),
                                    Forms\Components\DatePicker::make('administered_at')
                                        ->required(),
                                    Forms\Components\DatePicker::make('valid_until')
                                        ->required(),
                                    Forms\Components\TextInput::make('vet_name')
                                        ->label('Signature (Vet Name)'),
                                ])
                                ->columns(5)
                                ->collapsible(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Metrics History')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Forms\Components\Repeater::make('metrics')
                                ->relationship('metrics')
                                ->schema([
                                    Forms\Components\TextInput::make('metric_type')
                                        ->placeholder('Weight, Temp, HR')
                                        ->required(),
                                    Forms\Components\TextInput::make('value')
                                        ->required(),
                                    Forms\Components\TextInput::make('unit')
                                        ->required(),
                                    Forms\Components\DatePicker::make('measured_at')
                                        ->default(now())
                                        ->required(),
                                    Forms\Components\Textarea::make('notes')
                                        ->rows(1),
                                ])
                                ->columns(5)
                                ->collapsible(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('species')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'dog' => 'info',
                    'cat' => 'success',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('breed'),
            Tables\Columns\TextColumn::make('passport_number')
                ->label('Passport')
                ->toggleable(),
            Tables\Columns\TextColumn::make('weight')
                ->suffix(' kg'),
            Tables\Columns\IconColumn::make('chip_number')
                ->label('Chipped')
                ->boolean(fn ($state) => !empty($state)),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }
}