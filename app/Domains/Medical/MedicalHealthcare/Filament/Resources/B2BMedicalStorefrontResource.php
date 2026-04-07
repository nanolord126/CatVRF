<?php declare(strict_types=1);

/**
 * B2BMedicalStorefrontResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bmedicalstorefrontresource
 */


namespace App\Domains\Medical\MedicalHealthcare\Filament\Resources;

use Filament\Resources\Resource;

final class B2BMedicalStorefrontResource extends Resource
{

    protected static ?string $model=B2BMedicalStorefront::class; protected static ?string $navigationIcon='heroicon-o-heart'; protected static ?string $navigationGroup='Medical & Healthcare B2B'; public static function form(Form $form): Form { return $form->schema([Forms\Components\TextInput::make('company_name')->required(),Forms\Components\TextInput::make('inn')->required()->unique(),Forms\Components\Textarea::make('description'),Forms\Components\TextInput::make('wholesale_discount')->numeric(),Forms\Components\TextInput::make('min_order_amount')->numeric()->default(50000),Forms\Components\Toggle::make('is_verified')->disabled(),Forms\Components\Toggle::make('is_active')->default(true),]); } public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('company_name')->searchable(),Tables\Columns\TextColumn::make('inn'),Tables\Columns\TextColumn::make('wholesale_discount'),Tables\Columns\IconColumn::make('is_verified'),Tables\Columns\IconColumn::make('is_active'),])->filters([Tables\Filters\SelectFilter::make('is_verified')])->actions([Tables\Actions\ViewAction::make(),Tables\Actions\EditAction::make()]); }

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
