<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class MedicalDoctorResource extends Resource
{

    protected static ?string $model = \App\Domains\Medical\Models\Doctor::class;

        protected static ?string $navigationIcon = 'heroicon-o-user-plus';
        protected static ?string $navigationGroup = 'Medical Platform';
        protected static ?string $slug = 'medical-doctors';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Professional Profile')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('clinic_id')
                            ->relationship('clinic', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('specialization')
                            ->required(),

                        Forms\Components\TagsInput::make('sub_specializations'),

                        Forms\Components\TextInput::make('experience_years')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active in Schedule'),
                    ])->columns(2),

                Forms\Components\Section::make('Analytics & Tags')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->default(5.0)
                            ->disabled(),

                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add tags for AI analysis'),
                    ])->columns(2),

                Forms\Components\RichEditor::make('bio')
                    ->label('Public Biography')
                    ->columnSpanFull(),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMedicalDoctor::route('/'),
                'create' => Pages\CreateMedicalDoctor::route('/create'),
                'edit' => Pages\EditMedicalDoctor::route('/{record}/edit'),
                'view' => Pages\ViewMedicalDoctor::route('/{record}'),
            ];
        }
}
