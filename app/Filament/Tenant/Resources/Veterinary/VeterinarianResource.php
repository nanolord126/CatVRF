<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Veterinary;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class VeterinarianResource extends Resource
{

    protected static ?string $model = Veterinarian::class;

        protected static ?string $navigationIcon = 'heroicon-o-user-group';

        protected static ?string $navigationGroup = 'Veterinary & Pets';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        protected static ?int $navigationSort = 4;

        /**
         * Comprehensive Form Design (>= 60 lines)
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Section::make('Professional Profile')
                                ->description('Основная информация о враче')
                                ->schema([
                                    Select::make('clinic_id')
                                        ->relationship('clinic', 'name')
                                        ->label('Clinic Branch')
                                        ->required()
                                        ->searchable()
                                        ->preload(),

                                    TextInput::make('full_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Иванов Иван Иванович'),

                                    Repeater::make('specialization')
                                        ->label('Specializations')
                                        ->schema([
                                            TextInput::make('spec')
                                                ->label('Field (eg Surgery, Cardiology)')
                                                ->required(),
                                        ])
                                        ->columnSpanFull(),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('experience_years')
                                                ->numeric()
                                                ->required()
                                                ->default(0)
                                                ->label('Experience (Years)'),
                                            TextInput::make('license_number')
                                                ->maxLength(50)
                                                ->label('Medical License'),
                                        ]),
                                ])->columnSpan(2),

                            Section::make('Performance & Status')
                                ->description('Метрики и состояние врача')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Doctor is Active/Working')
                                        ->default(true),

                                    TextInput::make('rating')
                                        ->numeric()
                                        ->disabled()
                                        ->label('Average Rating'),

                                    TextInput::make('review_count')
                                        ->numeric()
                                        ->disabled()
                                        ->label('Review Count'),

                                    TextInput::make('correlation_id')
                                        ->disabled()
                                        ->label('Tracing ID'),
                                ])->columnSpan(1),

                            Section::make('Bio & Metadata')
                                ->description('Дополнительная информация')
                                ->schema([
                                    Textarea::make('biography')
                                        ->rows(4)
                                        ->label('Professional Bio')
                                        ->placeholder('Краткая профессиональная биография...'),

                                    Repeater::make('tags')
                                        ->schema([
                                            TextInput::make('tag')
                                                ->label('Keyword')
                                                ->required(),
                                        ])
                                        ->columnSpanFull(),
                                ])->columnSpanFull(),
                        ]),
                ]);
        }

        /**
         * Comprehensive Table Design (>= 50 lines)
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('full_name')
                        ->searchable()
                        ->sortable()
                        ->description(fn (Veterinarian $record): ?string => $record->clinic->name),

                    TextColumn::make('specialization')
                        ->label('Expertise')
                        ->formatStateUsing(fn ($state): string => collect($state)->pluck('spec')->implode(', '))
                        ->searchable(),

                    TextColumn::make('experience_years')
                        ->label('Exp. (Years)')
                        ->numeric()
                        ->sortable(),

                    TextColumn::make('rating')
                        ->numeric(decimalPlaces: 1)
                        ->color('warning')
                        ->sortable(),

                    IconColumn::make('is_active')
                        ->boolean()
                        ->label('Active'),

                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->label('By Clinic')
                        ->searchable()
                        ->preload(),

                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Status (Active Only)'),

                    Tables\Filters\Filter::make('senior_doctors')
                        ->query(fn (Builder $query): Builder => $query->where('experience_years', '>=', 10))
                        ->label('Experienced (10+ Years)'),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ])
                ->emptyStateHeading('No Doctors Added')
                ->emptyStateDescription('Добавьте первого ветеринара в вашу клинику.')
                ->emptyStateIcon('heroicon-o-user-group');
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }
}
