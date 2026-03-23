<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PharmacyResource;

use App\Domains\Pharmacy\Models\Pharmacy;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;

final class PharmacyResource extends Resource
{
    protected static ?string $model = Pharmacy::class;
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Main')->schema([
                TextInput::make('name')->required(),
                TextInput::make('address')->required(),
            ])
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->sortable()->searchable(),
        ]);
    }
}
