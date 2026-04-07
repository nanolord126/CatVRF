<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Carbon\Carbon;

use Filament\Resources\Resource;

final class ServiceCategoryResource extends Resource
{

    protected static ?string $model = ServiceCategory::class;
        protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
        protected static ?string $navigationLabel = 'Категории';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\TextInput::make('name')->label('Название')->required(),
                Forms\Components\RichEditor::make('description')->label('Описание'),
                Forms\Components\TextInput::make('icon')->label('Иконка'),
                Forms\Components\Toggle::make('is_active')->label('Активна'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('Категория')->searchable(),
                    Tables\Columns\TextColumn::make('icon')->label('Иконка'),
                    Tables\Columns\IconColumn::make('is_active')->label('Активна')->boolean(),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages\ListServiceCategories::route('/'),
                'create' => \App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages\CreateServiceCategory::route('/create'),
                'edit' => \App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant()->id);
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
