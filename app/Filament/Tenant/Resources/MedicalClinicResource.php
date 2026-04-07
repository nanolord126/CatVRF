<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Resource;

final class MedicalClinicResource extends Resource
{

    protected static ?string $model = MedicalClinic::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
        protected static ?string $navigationGroup = 'Medical Platform';
        protected static ?string $slug = 'medical-clinics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('license_number')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Location & Contact')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->email(),
                    ])->columns(3),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TagsInput::make('specializations')
                            ->required(),
                        Forms\Components\KeyValue::make('schedule')
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified by Platform')
                            ->disabled(!$this->guard->user()->is_admin),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMedicalClinic::route('/'),
                'create' => Pages\CreateMedicalClinic::route('/create'),
                'edit' => Pages\EditMedicalClinic::route('/{record}/edit'),
                'view' => Pages\ViewMedicalClinic::route('/{record}'),
            ];
        }
}
