<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use Filament\Resources\Resource;

final class B2BRealEstateStorefrontResource extends Resource
{

    protected static ?string $model = B2BRealEstateStorefront::class;
    	protected static ?string $navigationIcon = 'heroicon-o-home';
    	protected static ?string $navigationGroup = 'RealEstate B2B';

    	public static function form(Form $form): Form
    	{
    		return $form->schema([
    			Forms\Components\TextInput::make('company_name')->required(),
    			Forms\Components\TextInput::make('inn')->required()->unique(),
    			Forms\Components\Textarea::make('description'),
    			Forms\Components\TextInput::make('wholesale_discount')->numeric(),
    			Forms\Components\TextInput::make('min_order_amount')->numeric()->default(100000),
    			Forms\Components\Toggle::make('is_verified')->disabled(),
    			Forms\Components\Toggle::make('is_active')->default(true),
    		]);
    	}

    	public static function table(Table $table): Table
    	{
    		return $table->columns([
    			Tables\Columns\TextColumn::make('company_name')->searchable(),
    			Tables\Columns\TextColumn::make('inn'),
    			Tables\Columns\TextColumn::make('wholesale_discount'),
    			Tables\Columns\IconColumn::make('is_verified'),
    			Tables\Columns\IconColumn::make('is_active'),
    		])->filters([Tables\Filters\SelectFilter::make('is_verified')])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()]);
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
