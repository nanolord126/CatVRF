<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionAndRepair;

    use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Tables;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class ServiceProviderResource extends Resource
    {
        protected static ?string $model = ServiceProvider::class;
        protected static ?string $navigationIcon = 'heroicon-m-wrench-screwdriver';
        protected static ?string $navigationGroup = 'Construction & Repair';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🔨 Информация мастера')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Имя/компания')->required()->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->copyable()->columnSpan(1),
                        TextInput::make('email')->label('E-mail')->email()->copyable()->columnSpan(1),
                        RichEditor::make('description')->label('О компании')->columnSpan('full'),
                        FileUpload::make('logo')->label('Логотип')->image()->directory('construction')->columnSpan(1),
                    ])->columns(4),

                Section::make('Услуги')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('service_type')->label('Тип услуги')->options([
                            'renovation' => 'Ремонт',
                            'plumbing' => 'Сантехника',
                            'electrical' => 'Электрика',
                            'painting' => 'Покраска',
                            'carpentry' => 'Столярные работы',
                            'roofing' => 'Кровля',
                            'masonry' => 'Кладка',
                            'demolition' => 'Демонтаж',
                        ])->required()->columnSpan(2),
                        TagsInput::make('specializations')->label('Специализации')->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_verified')->label('✓ Проверен')->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Мастер')->searchable()->sortable()->icon('heroicon-m-wrench-screwdriver')->limit(40),
                BadgeColumn::make('service_type')->label('Услуга')->formatStateUsing(fn ($state) => match($state) { 'renovation' => 'Ремонт', 'plumbing' => 'Сантехника', 'electrical' => 'Электрика', default => 'Прочее' })->color(fn ($state) => match($state) { 'renovation' => 'blue', 'plumbing' => 'cyan', 'electrical' => 'yellow', default => 'gray' }),
                TextColumn::make('phone')->label('Телефон')->copyable(),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_verified')->label('✓ Проверен')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('service_type')->label('Услуга')->options(['renovation' => 'Ремонт', 'plumbing' => 'Сантехника', 'electrical' => 'Электрика', 'painting' => 'Покраска'])->multiple(),
                TernaryFilter::make('is_verified')->label('Проверен'),
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
                'index' => \App\Filament\Tenant\Resources\ConstructionAndRepair\Pages\ListProviders::route('/'),
                'create' => \App\Filament\Tenant\Resources\ConstructionAndRepair\Pages\CreateProvider::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\ConstructionAndRepair\Pages\EditProvider::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
