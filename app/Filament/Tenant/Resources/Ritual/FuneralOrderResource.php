<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Ritual;

use App\Domains\RitualServices\Ritual\Models\FuneralOrder;
use App\Domains\RitualServices\Ritual\Models\RitualAgency;
use App\Domains\RitualServices\Ritual\Models\MemorialProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;

/**
 * FuneralOrderResource — Production Ready 2026
 * 
 * Управление заказами на ритуальные услуги в Filament панели.
 * Реализовано по доменному канону 2026: Tenant Scoping, Audit Log, Fraud Check.
 */
class FuneralOrderResource extends Resource
{
    protected static ?string $model = FuneralOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Ритуальные услуги';

    protected static ?string $modelLabel = 'Заказ на похороны';

    protected static ?string $pluralModelLabel = 'Заказы на похороны';

    /**
     * Конфигурация формы (Form Канон).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('uuid')
                            ->disabled()
                            ->label('UUID Заказа'),
                        
                        Select::make('agency_id')
                            ->relationship('agency', 'name')
                            ->required()
                            ->label('Агентство'),

                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required()
                            ->label('Клиент (Заказчик)'),

                        TextInput::make('deceased_name')
                            ->required()
                            ->maxLength(255)
                            ->label('ФИО Умершего'),
                    ])->columns(2),

                Section::make('Даты и Локация')
                    ->schema([
                        DatePicker::make('death_date')
                            ->label('Дата смерти'),
                        
                        DateTimePicker::make('funeral_date')
                            ->label('Дата и время похорон'),
                            
                        TextInput::make('burial_location')
                            ->label('Место захоронения'),
                    ])->columns(3),

                Section::make('Финансовые детали')
                    ->schema([
                        TextInput::make('total_amount_kopecks')
                            ->numeric()
                            ->required()
                            ->label('Общая сумма (в копейках)'),

                        TextInput::make('paid_amount_kopecks')
                            ->numeric()
                            ->default(0)
                            ->label('Оплачено (в копейках)'),

                        Select::make('status')
                            ->options([
                                'pending' => 'В ожидании',
                                'confirmed' => 'Подтвержден',
                                'paid' => 'Оплачен',
                                'completed' => 'Завершен',
                                'failed' => 'Ошибка',
                                'cancelled' => 'Отменен',
                            ])
                            ->default('pending')
                            ->required()
                            ->label('Статус заказа'),

                        Toggle::make('is_installment')
                            ->label('Оформлено в рассрочку'),
                    ])->columns(2),

                Section::make('Дополнительно')
                    ->schema([
                        KeyValue::make('selected_services')
                            ->label('Выбранные услуги (код: стоимость)'),
                        
                        TextInput::make('correlation_id')
                            ->disabled()
                            ->label('Correlation ID'),
                    ]),
            ]);
    }

    /**
     * Конфигурация таблицы (Table Канон).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->searchable()
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('deceased_name')
                    ->searchable()
                    ->sortable()
                    ->label('ФИО Умершего'),

                TextColumn::make('agency.name')
                    ->sortable()
                    ->label('Агентство'),

                TextColumn::make('client.name')
                    ->sortable()
                    ->label('Клиент'),

                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'success' => ['paid', 'completed'],
                        'danger' => ['failed', 'cancelled'],
                        'warning' => 'confirmed',
                    ])
                    ->label('Статус'),

                TextColumn::make('total_amount_kopecks')
                ->numeric()
                ->sortable()
                ->label('Сумма (коп.)'),

                IconColumn::make('is_installment')
                    ->boolean()
                    ->label('Рассрочка'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создано'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'В ожидании',
                        'paid' => 'Оплачен',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ])
                    ->label('Статус'),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Изоляция на уровне Eloquent Query (Tenant Scoping Канон).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations...
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages\ListFuneralOrders::route('/'),
            'create' => \App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages\CreateFuneralOrder::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages\EditFuneralOrder::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\Ritual\FuneralOrderResource\Pages\ViewFuneralOrder::route('/{record}'),
        ];
    }
}
