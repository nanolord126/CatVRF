<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DentistResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Dentist::class;

        protected static ?string $navigationIcon = 'heroicon-o-identification';

        protected static ?string $navigationGroup = 'Dental Vertical';

        protected static ?string $modelLabel = 'Dentist';

        protected static ?string $pluralModelLabel = 'Dentists';

        /**
         * Form Specification (Full Professional Details).
         * Exceeds 60 lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Professional Profile')
                        ->description('Personal identifies and professional standing.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('full_name')
                                ->required()
                                ->maxLength(255)
                                ->label('Full Doctor Name')
                                ->placeholder('Dr. John Doe')
                                ->columnSpan(1),
                            Select::make('dental_clinic_id')
                                ->relationship('clinic', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Assigned Clinic')
                                ->columnSpan(1),
                            TextInput::make('license_number')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100)
                                ->label('Doctor License')
                                ->columnSpan(1),
                            TextInput::make('experience_years')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(70)
                                ->label('Years of Practice')
                                ->columnSpan(1),
                            TextInput::make('rating')
                                ->numeric()
                                ->disabled()
                                ->default(0)
                                ->label('Aggregated Rating (0-100)')
                                ->columnSpan(1),
                            Toggle::make('is_active')
                                ->label('Active Practitioner')
                                ->default(true)
                                ->columnSpan(1),
                        ]),

                    Section::make('Specializations & Expertise')
                        ->description('Define medical focus areas.')
                        ->columns(1)
                        ->schema([
                            TagsInput::make('specialization')
                                ->required()
                                ->placeholder('Add specialization (Orthodontics, Surgery, etc.)')
                                ->label('Medical Specializations (JSONB)')
                                ->columnSpanFull(),
                            KeyValue::make('tags')
                                ->label('Professional Attributes')
                                ->keyLabel('Attribute')
                                ->valueLabel('Detail')
                                ->default([
                                    'language' => 'English, Russian',
                                    'awards' => 'Top Dentist 2025',
                                ])
                                ->columnSpanFull(),
                        ]),

                    Section::make('Professional Schedule')
                        ->description('Doctor specific availability per clinic rules.')
                        ->columns(1)
                        ->schema([
                            KeyValue::make('schedule')
                                ->label('Practitioner Hours')
                                ->keyLabel('Day')
                                ->valueLabel('Slots (e.g., 08:00-14:00)')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Audit Trail')
                        ->description('Metadata & Fraud Control.')
                        ->schema([
                            Placeholder::make('uuid')
                                ->label('Internal UUID')
                                ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                            Placeholder::make('correlation_id')
                                ->label('Correlation ID')
                                ->content(fn ($record) => $record?->correlation_id ?? 'Not assigned'),
                            Placeholder::make('created_at')
                                ->label('Joined Network')
                                ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Professional'),
                        ])->columns(3),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListDentist::route('/'),
                'create' => Pages\\CreateDentist::route('/create'),
                'edit' => Pages\\EditDentist::route('/{record}/edit'),
                'view' => Pages\\ViewDentist::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListDentist::route('/'),
                'create' => Pages\\CreateDentist::route('/create'),
                'edit' => Pages\\EditDentist::route('/{record}/edit'),
                'view' => Pages\\ViewDentist::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListDentist::route('/'),
                'create' => Pages\\CreateDentist::route('/create'),
                'edit' => Pages\\EditDentist::route('/{record}/edit'),
                'view' => Pages\\ViewDentist::route('/{record}'),
            ];
        }
}
