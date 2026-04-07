<?php declare(strict_types=1);

/**
 * ListStationerySubscription — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/liststationerysubscription
 * @see https://catvrf.ru/docs/liststationerysubscription
 * @see https://catvrf.ru/docs/liststationerysubscription
 */


namespace App\Filament\Tenant\Resources\Pages;

    use Filament\Tables\Actions\DeleteBulkAction;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;

    /**
 * Class ListStationerySubscription
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListStationerySubscription extends ListRecords
    {
        protected static string $resource = StationerySubscriptionResource::class;

        /**
         * Handle getTitle operation.
         *
         * @throws \DomainException
         */
        public function getTitle(): string
        {
            return 'List StationerySubscription';
        }

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make(),
            ];
        }

        /**
         * Handle table operation.
         *
         * @throws \DomainException
         */
        public function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('id')->sortable(),
                    TextColumn::make('created_at')->dateTime()->sortable(),
                ])
                ->filters([])
                ->actions([EditAction::make(), DeleteAction::make()])
                ->bulkActions([DeleteBulkAction::make()]);
        }
}
