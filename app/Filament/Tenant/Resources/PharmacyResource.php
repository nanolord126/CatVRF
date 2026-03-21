<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Pharmacy\Models\Pharmacy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PharmacyResource extends Resource
{
    protected static ?string $model = Pharmacy::class;
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationGroup = 'Healthcare';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            TextInput::make('mnn')->required(),
            Select::make('form')->options([
                'tablet' => 'Таблетка', 'capsule' => 'Капсула', 'syrup' => 'Сироп',
                'drops' => 'Капли', 'ointment' => 'Мазь', 'injection' => 'Инъекция',
            ])->required(),
            TextInput::make('dosage')->required(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('current_stock')->numeric(),
            Toggle::make('is_otc'),
            Toggle::make('requires_prescription'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('mnn'),
            TextColumn::make('form'),
            TextColumn::make('dosage'),
            TextColumn::make('price')->formatStateUsing(fn($s) => $s . ' ₽'),
            IconColumn::make('is_otc')->boolean(),
            IconColumn::make('requires_prescription')->boolean(),
            TextColumn::make('rating'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkDeleteAction::make(),
        ]);
    }
}
