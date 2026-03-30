<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceJobResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = ServiceJob::class;
        protected static ?string $navigationIcon = 'heroicon-o-briefcase';
        protected static ?string $navigationLabel = 'Заказы';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Заказ')
                    ->schema([
                        Forms\Components\Select::make('service_listing_id')->label('Услуга')->relationship('serviceListing', 'name')->required(),
                        Forms\Components\Select::make('contractor_id')->label('Подрядчик')->relationship('contractor', 'company_name')->required(),
                        Forms\Components\Select::make('client_id')->label('Клиент')->relationship('client', 'email')->required(),
                    ]),
                Forms\Components\Section::make('Детали')
                    ->schema([
                        Forms\Components\RichEditor::make('description')->label('Описание'),
                        Forms\Components\TextInput::make('address')->label('Адрес'),
                        Forms\Components\DateTimePickerInput::make('scheduled_at')->label('Запланировано'),
                    ]),
                Forms\Components\Section::make('Статус и Сумма')
                    ->schema([
                        Forms\Components\Select::make('status')->label('Статус')->options([
                            'pending' => 'Ожидание',
                            'accepted' => 'Принято',
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершено',
                            'cancelled' => 'Отменено',
                        ])->disabled(),
                        Forms\Components\TextInput::make('base_amount')->label('Сумма (без комиссии)')->numeric()->disabled(),
                        Forms\Components\TextInput::make('commission_amount')->label('Комиссия (14%)')->numeric()->disabled(),
                        Forms\Components\TextInput::make('total_amount')->label('Итого')->numeric()->disabled(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('serviceListing.name')->label('Услуга')->searchable(),
                    Tables\Columns\TextColumn::make('contractor.company_name')->label('Подрядчик'),
                    Tables\Columns\TextColumn::make('client.email')->label('Клиент'),
                    Tables\Columns\TextColumn::make('status')->label('Статус')->badge(),
                    Tables\Columns\TextColumn::make('total_amount')->label('Сумма')->money('RUB'),
                    Tables\Columns\TextColumn::make('scheduled_at')->label('Запланировано')->dateTime(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')->options([
                        'pending' => 'Ожидание',
                        'accepted' => 'Принято',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages\ListServiceJobs::route('/'),
                'view' => \App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages\ViewServiceJob::route('/{record}'),
                'edit' => \App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages\EditServiceJob::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
