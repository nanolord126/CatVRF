<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning;


use Psr\Log\LoggerInterface;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class LanguageSchoolResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static ?string $model = LanguageSchool::class;
        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
        protected static ?string $navigationGroup = 'Language Learning';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Public identity of the language school')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->label('School Name'),

                            Forms\Components\Textarea::make('description')
                                ->maxLength(1000)
                                ->columnSpan(2)
                                ->placeholder('Describe the school methodology and mission'),

                            Forms\Components\TextInput::make('address')
                                ->columnSpan(2)
                                ->label('Physical Address (Office/Branch)'),
                        ])->columns(2),

                    Forms\Components\Section::make('Education Details')
                        ->schema([
                            Forms\Components\TagsInput::make('languages')
                                ->required()
                                ->placeholder('Add language (English, Italian, etc.)')
                                ->label('Taught Languages'),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Verified by Platform')
                                ->default(false)
                                ->onIcon('heroicon-m-check-badge')
                                ->offColor('danger'),
                        ])->columns(2),

                    Forms\Components\Section::make('Financial & B2B Strategy')
                        ->description('Configuration for corporate and retail operations')
                        ->schema([
                            Forms\Components\KeyValue::make('settings')
                                ->keyLabel('Setting Name')
                                ->valueLabel('Value / Percent')
                                ->addButtonLabel('Add rule')
                                ->helperText('e.g., b2b_discount: 15, max_students: 20'),

                            Forms\Components\Placeholder::make('uuid_display')
                                ->label('System UUID')
                                ->content(fn ($record) => $record?->uuid ?? 'Generating...'),
                        ])->columns(1),

                    Forms\Components\Section::make('Internal Audit')
                        ->schema([
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->label('Audit Correlation ID')
                                ->placeholder(Str::uuid()),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Analytical Tags'),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->weight('bold')
                        ->label('School Name'),

                    Tables\Columns\ImageColumn::make('avatar')
                        ->circular()
                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),

                    Tables\Columns\TextColumn::make('languages')
                        ->badge()
                        ->separator(',')
                        ->label('Languages'),

                    Tables\Columns\IconColumn::make('is_verified')
                        ->boolean()
                        ->label('Verified'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextColumn::make('correlation_id')
                        ->label('Trace ID')
                        ->fontFamily('mono')
                        ->size('xs')
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_verified'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make()
                        ->before(function ($record) {
                            $this->logger->info('Filament: School edit started', [
                                'school_id' => $record->id,
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

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListLanguageSchools::route('/'),
                'create' => Pages\CreateLanguageSchool::route('/create'),
                'edit' => Pages\EditLanguageSchool::route('/{record}/edit'),
            ];
        }
}
