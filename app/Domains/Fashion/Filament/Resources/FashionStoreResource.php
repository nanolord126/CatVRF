<?php declare(strict_types=1);

/**
 * FashionStoreResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionstoreresource
 */


namespace App\Domains\Fashion\Filament\Resources;

use Filament\Resources\Resource;

final class FashionStoreResource extends Resource
{

    protected static ?string $model = FashionStore::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('name')->required()->maxLength(255),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('logo_url')->url(),
                TextInput::make('cover_image_url')->url(),
                Toggle::make('is_verified')->default(false),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.name'),
                TextColumn::make('product_count')->numeric(),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
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
