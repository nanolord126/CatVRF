<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament-ресурс для управления салонами красоты.
 *
 * Tenant-scoped: показывает только салоны текущего тенанта.
 * Используется в Tenant Panel (/tenant) и Admin Panel.
 */
final class BeautySalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

    protected static ?string $slug = 'marketplace/beauty/salons';

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Salons';

    /**
     * CANON 2026: Tenant scoping в Filament-ресурсе.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (function_exists('tenant') && tenant()->id) {
            $query->where('tenant_id', tenant()->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Salon Info')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('phone')->required(),
                TextInput::make('email')->required()->email(),
                TextInput::make('address')->required(),
                Select::make('owner_id')->relationship('owner', 'name')->required(),
            ])->columns(2),

            Section::make('Details')->schema([
                RichEditor::make('description')->columnSpanFull(),
            ]),

            Section::make('Status')->schema([
                Select::make('is_active')->options([
                    true => 'Active',
                    false => 'Inactive',
                ])->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('owner.name')->searchable(),
            TextColumn::make('phone'),
            TextColumn::make('email')->searchable(),
            TextColumn::make('address'),
            TextColumn::make('rating')->numeric(),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([])->actions([])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => (new class extends ListRecords {
                protected static string $resource = BeautySalonResource::class;
            })::route('/'),
            'create' => (new class extends CreateRecord {
                protected static string $resource = BeautySalonResource::class;
            })::route('/create'),
            'edit' => (new class extends EditRecord {
                protected static string $resource = BeautySalonResource::class;
            })::route('/{record}/edit'),
        ];
    }
}
