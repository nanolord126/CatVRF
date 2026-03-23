<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCateringResource;

use App\Domains\OfficeCatering\Models\CorporateClient;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;

final class OfficeCateringResource extends Resource
{
    protected static ?string $model = CorporateClient::class;
    public static function form(Form $f): Form
    {
        return $f->schema([Section::make('Info')->schema([TextInput::make('name')->required()])]);
    }
    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('name')->sortable()->searchable()]);
    }
}
