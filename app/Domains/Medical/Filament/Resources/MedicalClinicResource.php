<?php declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Filament\Resources\Resource;

final class MedicalClinicResource extends Resource
{

    protected static ?string $model = MedicalClinic::class;

        protected static ?string $navigationGroup = 'Medical';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('name')->required(),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('address')->required(),
                TextInput::make('phone')->required(),
                TextInput::make('email')->required()->email(),
                TextInput::make('license_number')->unique(),
                Toggle::make('is_verified')->default(false),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.name'),
                TextColumn::make('doctor_count')->numeric(),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
