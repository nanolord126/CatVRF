<?php declare(strict_types=1);

namespace App\Filament\CRM\Resources;

use App\Models\User;
use App\Services\AuditService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * CRM: управление клиентами (Users) с историей взаимодействий.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Только просмотр и базовые операции.
 * Мутации баланса/статуса идут через сервисы с FraudControlService::check().
 */
final class CustomerResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Клиенты';
    protected static ?string $navigationLabel = 'Клиенты';
    protected static ?string $modelLabel = 'Клиент';
    protected static ?string $pluralModelLabel = 'Клиенты';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'customers';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основные данные')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->columnSpan(1),
                    DatePicker::make('created_at')
                        ->label('Дата регистрации')
                        ->disabled()
                        ->columnSpan(1),
                ]),
            Section::make('Заметка CRM')
                ->collapsed()
                ->schema([
                    Textarea::make('crm_note')
                        ->label('Заметка менеджера')
                        ->rows(4)
                        ->maxLength(2000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('orders_count')
                    ->label('Заказов')
                    ->counts('orders')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label('Email верифицирован')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('has_orders')
                    ->label('Наличие заказов')
                    ->options([
                        'yes' => 'С заказами',
                        'no'  => 'Без заказов',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'no'    => $query->whereDoesntHave('orders'),
                        default => $query,
                    }),
                Filter::make('verified')
                    ->label('Верифицированные')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Filter::make('new_30days')
                    ->label('Новые (30 дней)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('sendNote')
                    ->label('Заметка')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->form([
                        Textarea::make('note')
                            ->label('Заметка')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (User $record, array $data): void {
                        app(AuditService::class)->record(
                            'crm_note_added',
                            User::class,
                            $record->id,
                            [],
                            ['note_preview' => mb_substr($data['note'], 0, 100)],
                        );

                        Notification::make()
                            ->title('Заметка сохранена')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\CRM\Resources\CustomerResource\Pages\ListCustomers::route('/'),
            'view'  => \App\Filament\CRM\Resources\CustomerResource\Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
