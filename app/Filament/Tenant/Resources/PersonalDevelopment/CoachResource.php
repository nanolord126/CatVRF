<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PersonalDevelopment;

    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class CoachResource extends Resource
    {
        protected static ?string $model = Coach::class;
        protected static ?string $navigationIcon = 'heroicon-m-user-group';
        protected static ?string $navigationGroup = 'Education & Development';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('👨‍🏫 Информация о коахе')
                    ->icon('heroicon-m-user-group')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('full_name')->label('ФИ О')->required()->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        Select::make('specialization')->label('Специализация')->options([
                            'life_coach' => 'Life Coach',
                            'career_coach' => 'Career Coach',
                            'business_coach' => 'Business Coach',
                            'health_coach' => 'Health Coach',
                            'relationships' => 'Отношения',
                            'public_speaking' => 'Ораторство',
                        ])->required()->columnSpan(2),
                        TextInput::make('experience_years')->label('Лет опыта')->numeric()->columnSpan(1),
                        TextInput::make('hourly_rate')->label('Цена/час (₽)')->numeric()->required()->columnSpan(1),
                        RichEditor::make('bio')->label('Био')->columnSpan('full'),
                        FileUpload::make('avatar')->label('Фото')->image()->directory('coaches')->columnSpan(1),
                    ])->columns(4),

                Section::make('Возможности')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Toggle::make('online_sessions')->label('📹 Онлайн-сессии')->columnSpan(1),
                        Toggle::make('group_sessions')->label('👥 Групповые')->columnSpan(1),
                        Toggle::make('corporate_training')->label('🏢 Корпоративы')->columnSpan(1),
                        Toggle::make('has_certification')->label('📜 Сертифицирован')->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Популярный')->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
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
                TextColumn::make('full_name')->label('Коач')->searchable()->sortable()->limit(40),
                BadgeColumn::make('specialization')->label('Спец.')->color(fn ($state) => match($state) { 'life_coach' => 'blue', 'career_coach' => 'purple', 'business_coach' => 'green', 'health_coach' => 'red', 'relationships' => 'pink', 'public_speaking' => 'orange' }),
                TextColumn::make('experience_years')->label('Опыт (л)')->numeric()->alignment('center'),
                TextColumn::make('hourly_rate')->label('Час (₽)')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('online_sessions')->label('📹')->toggleable(),
                BooleanColumn::make('has_certification')->label('📜')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('specialization')->label('Специализация')->options([
                    'life_coach' => 'Life Coach',
                    'career_coach' => 'Career Coach',
                    'business_coach' => 'Business Coach',
                    'health_coach' => 'Health Coach',
                ])->multiple(),
                TernaryFilter::make('online_sessions')->label('Онлайн'),
                TernaryFilter::make('has_certification')->label('Сертифицированы'),
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
                'index' => \App\Filament\Tenant\Resources\PersonalDevelopment\Pages\ListCoaches::route('/'),
                'create' => \App\Filament\Tenant\Resources\PersonalDevelopment\Pages\CreateCoach::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\PersonalDevelopment\Pages\EditCoach::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
