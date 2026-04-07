<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CarRental;

    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class VehicleResource extends Resource
    {
        protected static ?string $model = Vehicle::class;
        protected static ?string $navigationIcon = 'heroicon-m-truck';
        protected static ?string $navigationGroup = 'Auto & Mobility';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🚗 Информация об авто')
                    ->icon('heroicon-m-truck')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('registration_number')->label('Гос. номер')->required()->unique()->columnSpan(1),
                        TextInput::make('vin')->label('VIN')->unique()->columnSpan(1),
                        Select::make('brand')->label('Марка')->options(['toyota' => 'Toyota', 'bmw' => 'BMW', 'audi' => 'Audi', 'mercedes' => 'Mercedes', 'vw' => 'VW', 'ford' => 'Ford'])->required()->columnSpan(1),
                        TextInput::make('model')->label('Модель')->required()->columnSpan(1),
                        TextInput::make('year')->label('Год выпуска')->numeric()->required()->columnSpan(1),
                        Select::make('transmission')->label('КПП')->options(['manual' => 'Механика', 'automatic' => 'Автомат'])->columnSpan(1),
                        Select::make('car_class')->label('Класс')->options(['economy' => 'Эконом', 'comfort' => 'Комфорт', 'business' => 'Бизнес', 'premium' => 'Премиум'])->columnSpan(1),
                        TextInput::make('daily_price')->label('Цена в день (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('mileage')->label('Пробег (км)')->numeric()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('images')->label('Фото')->image()->directory('vehicles')->multiple()->columnSpan(1),
                    ])->columns(4),

                Section::make('Возможности')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Toggle::make('has_gps')->label('📍 GPS')->columnSpan(1),
                        Toggle::make('has_child_seat')->label('👶 Детское кресло')->columnSpan(1),
                        Toggle::make('has_wifi')->label('📶 WiFi')->columnSpan(1),
                        Toggle::make('is_electric')->label('⚡ Электро')->columnSpan(1),
                    ])->columns(4),

                Section::make('Статус')
                    ->icon('heroicon-m-exclamation-circle')
                    ->schema([
                        Select::make('status')->label('Статус')->options(['available' => '✅ Доступно', 'booked' => '📅 Забронировано', 'maintenance' => '🔧 Техническое', 'accident' => '⚠️ Авария'])->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Популярный')->columnSpan(1),
                    ])->columns(3),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('registration_number')->label('Номер')->searchable()->sortable()->badge()->color('info')->limit(20),
                TextColumn::make('brand')->label('Марка')->searchable(),
                TextColumn::make('model')->label('Модель')->searchable(),
                TextColumn::make('year')->label('Год')->numeric()->alignment('center'),
                BadgeColumn::make('car_class')->label('Класс')->color(fn ($state) => match($state) { 'economy' => 'blue', 'comfort' => 'cyan', 'business' => 'orange', 'premium' => 'yellow' }),
                TextColumn::make('daily_price')->label('День (₽)')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('status')->label('Статус')->color(fn ($state) => match($state) { 'available' => 'success', 'booked' => 'warning', 'maintenance' => 'info', 'accident' => 'danger' }),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('brand')->label('Марка')->options(['toyota' => 'Toyota', 'bmw' => 'BMW', 'audi' => 'Audi'])->multiple(),
                SelectFilter::make('car_class')->label('Класс')->options(['economy' => 'Эконом', 'comfort' => 'Комфорт', 'business' => 'Бизнес'])->multiple(),
                Filter::make('price_budget')->label('Бюджет день < 5000₽')->query(fn (Builder $query) => $query->where('daily_price', '<', 500000)),
                TrashedFilter::make(),
            ])->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
            ])->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->icon('heroicon-m-check-circle')->color('success')->action(function ($records) { $records->each(fn ($r) => $r->update(['is_active' => true])); })->deselectRecordsAfterCompletion()]),
            ])->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\CarRental\Pages\ListVehicles::route('/'),
                'create' => \App\Filament\Tenant\Resources\CarRental\Pages\CreateVehicle::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\CarRental\Pages\EditVehicle::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
