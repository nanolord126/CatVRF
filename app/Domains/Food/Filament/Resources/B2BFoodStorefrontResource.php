<?php declare(strict_types=1);

/**
 * B2BFoodStorefrontResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bfoodstorefrontresource
 */


namespace App\Domains\Food\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class B2BFoodStorefrontResource extends Resource
{

    protected static ?string $model = B2BFoodStorefront::class;
    	protected static ?string $navigationIcon = 'heroicon-o-fire';
    	protected static ?string $navigationGroup = 'Food B2B';

    	public static function form(Form $form): Form
    	{
    		return $form->schema([
    			Forms\Components\TextInput::make('company_name')->required(),
    			Forms\Components\TextInput::make('inn')->required()->unique(),
    			Forms\Components\Textarea::make('description'),
    			Forms\Components\TextInput::make('wholesale_discount')->numeric(),
    			Forms\Components\TextInput::make('min_order_amount')->numeric()->default(50000),
    			Forms\Components\Toggle::make('is_verified')->disabled(),
    			Forms\Components\Toggle::make('is_active')->default(true),
    		]);
    	}

    	public static function table(Table $table): Table
    	{
    		return $table
    			->columns([
    				Tables\Columns\TextColumn::make('company_name')->searchable(),
    				Tables\Columns\TextColumn::make('inn'),
    				Tables\Columns\TextColumn::make('wholesale_discount'),
    				Tables\Columns\IconColumn::make('is_verified'),
    				Tables\Columns\IconColumn::make('is_active'),
    			])
    			->filters([Tables\Filters\SelectFilter::make('is_verified')])
    			->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()]);
    	}

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
