<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet;

use App\Domains\Pet\Models\PetService;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\TagsInput, Components\Hidden, Components\RichEditor};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

final class PetServiceResource extends Resource
{
    protected static ?string $model = PetService::class;
    protected static ?string $navigationIcon = 'heroicon-m-heart';
    protected static ?string $navigationGroup = 'Pet & Veterinary';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->icon('heroicon-m-heart')
                ->schema([
                    TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn () => Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),

                    TextInput::make('name')
                        ->label('Название услуги')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('price')
                        ->label('Цена (₽)')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('duration_minutes')
                        ->label('Длительность (мин)')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    RichEditor::make('description')
                        ->label('Описание')
                        ->columnSpan('full'),
                ])->columns(4),

            Section::make('Категория и виды животных')
                ->icon('heroicon-m-tag')
                ->schema([
                    Select::make('service_type')
                        ->label('Тип услуги')
                        ->options([
                            'grooming' => 'Груминг',
                            'vaccination' => 'Вакцинация',
                            'surgery' => 'Хирургия',
                            'consultation' => 'Консультация',
                            'boarding' => 'Передержка',
                            'walking' => 'Прогулки',
                            'training' => 'Дрессировка',
                            'other' => 'Прочее',
                        ])
                        ->required()
                        ->columnSpan(2),

                    TagsInput::make('suitable_animals')
                        ->label('Подходит для')
                        ->columnSpan(2),
                ])->columns(4),

            Section::make('Расходники и запасы')
                ->icon('heroicon-m-cube-transparent')
                ->schema([
                    TagsInput::make('consumables_used')
                        ->label('Расходники (краска, шампунь и т.д.)')
                        ->columnSpan('full'),
                ])->columns('full'),

            Section::make('Статус')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true)
                        ->columnSpan(1),

                    Toggle::make('is_premium')
                        ->label('💎 Премиум')
                        ->columnSpan(1),
                ])->columns(2),

            Section::make('Служебная информация')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Hidden::make('tenant_id')
                        ->default(fn () => tenant('id')),

                    Hidden::make('correlation_id')
                        ->default(fn () => Str::uuid()),

                    Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('Услуга')
                ->searchable()
                ->sortable()
                ->icon('heroicon-m-heart')
                ->limit(40),

            BadgeColumn::make('service_type')
                ->label('Тип')
                ->formatStateUsing(fn ($state) => match($state) {
                    'grooming' => 'Груминг',
                    'vaccination' => 'Вакцинация',
                    'surgery' => 'Хирургия',
                    'consultation' => 'Консультация',
                    'boarding' => 'Передержка',
                    'walking' => 'Прогулки',
                    'training' => 'Дрессировка',
                    default => 'Прочее',
                })
                ->color(fn ($state) => match($state) {
                    'grooming' => 'blue',
                    'vaccination' => 'success',
                    'surgery' => 'danger',
                    'consultation' => 'info',
                    'boarding' => 'warning',
                    'walking' => 'cyan',
                    'training' => 'purple',
                    default => 'gray',
                }),

            TextColumn::make('price')
                ->label('Цена')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            TextColumn::make('duration_minutes')
                ->label('Длит. (мин)')
                ->numeric()
                ->alignment('center'),

            BooleanColumn::make('is_active')
                ->label('Активна')
                ->toggleable()
                ->sortable(),

            BooleanColumn::make('is_premium')
                ->label('💎 Премиум')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Создана')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            SelectFilter::make('service_type')
                ->label('Тип услуги')
                ->options([
                    'grooming' => 'Груминг',
                    'vaccination' => 'Вакцинация',
                    'surgery' => 'Хирургия',
                    'consultation' => 'Консультация',
                    'boarding' => 'Передержка',
                    'walking' => 'Прогулки',
                    'training' => 'Дрессировка',
                ])
                ->multiple(),

            TernaryFilter::make('is_active')
                ->label('Активна'),

            Filter::make('price_budget')
                ->label('До 5000 ₽')
                ->query(fn (Builder $query) => $query->where('price', '<', 500000)),

            TrashedFilter::make(),
        ])
        ->actions([
            Tables\Actions\ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),

                BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_active' => true]);
                            Log::channel('audit')->info('Pet service bulk activated', [
                                'service_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),
            ]),
        ])
        ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Pet\Pages\ListServices::route('/'),
            'create' => \App\Filament\Tenant\Resources\Pet\Pages\CreateService::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Pet\Pages\ViewService::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Pet\Pages\EditService::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }
}
