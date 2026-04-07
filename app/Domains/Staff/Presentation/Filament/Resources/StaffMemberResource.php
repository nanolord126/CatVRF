<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Filament\Resources;

use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Domain\Enums\StaffStatus;
use App\Domains\Staff\Domain\Enums\Vertical;
use App\Domains\Staff\Infrastructure\Persistence\Eloquent\Models\StaffMemberModel;
use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages\CreateStaffMember;
use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages\EditStaffMember;
use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages\ListStaffMembers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * StaffMemberResource — Filament B2B-ресурс для управления сотрудниками тенанта.
 *
 * Tenant-scoped: в getEloquentQuery() применяется filament()->getTenant()->id.
 */
final class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMemberModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $pluralModelLabel = 'Сотрудники';

    protected static ?string $modelLabel = 'Сотрудник';

    protected static ?string $slug = 'staff';

    protected static ?int $navigationSort = 30;

    // ─── Form ────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Личные данные')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('last_name')
                        ->label('Фамилия')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('first_name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('middle_name')
                        ->label('Отчество')
                        ->maxLength(100),
                ]),

            Forms\Components\Section::make('Контакты')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),
                ]),

            Forms\Components\Section::make('Должность и статус')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options(StaffStatus::options())
                        ->required()
                        ->default(StaffStatus::ACTIVE->value),

                    Forms\Components\Select::make('role')
                        ->label('Роль')
                        ->options(StaffRole::options())
                        ->required()
                        ->default(StaffRole::EMPLOYEE->value),

                    Forms\Components\Select::make('vertical')
                        ->label('Вертикаль')
                        ->options(Vertical::options())
                        ->required(),
                ]),

            Forms\Components\FileUpload::make('avatar_url')
                ->label('Фото сотрудника')
                ->image()
                ->imagePreviewHeight('80')
                ->directory('staff/avatars')
                ->columnSpanFull(),

        ]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('ФИО')
                    ->formatStateUsing(
                        fn (StaffMemberModel $record): string =>
                            trim("{$record->last_name} {$record->first_name} {$record->middle_name}")
                    )
                    ->searchable(['last_name', 'first_name', 'middle_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон'),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->label('Статус')
                    ->formatStateUsing(fn (string $state): string => StaffStatus::from($state)->label())
                    ->color(fn (string $state): string => StaffStatus::from($state)->color()),

                Tables\Columns\TextColumn::make('role')->badge()
                    ->label('Роль')
                    ->formatStateUsing(fn (string $state): string => StaffRole::from($state)->label())
                    ->color(fn (string $state): string => StaffRole::from($state)->color()),

                Tables\Columns\TextColumn::make('vertical')->badge()
                    ->label('Вертикаль')
                    ->formatStateUsing(fn (string $state): string => Vertical::from($state)->label()),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(StaffStatus::options()),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options(StaffRole::options()),

                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options(Vertical::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    // ─── Query ───────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $tenantId = filament()->getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->when($tenantId, fn (Builder $q) => $q->where('tenant_id', $tenantId))
            ->with([]);
    }

    // ─── Pages ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListStaffMembers::route('/'),
            'create' => CreateStaffMember::route('/create'),
            'edit'   => EditStaffMember::route('/{record}/edit'),
        ];
    }
}
