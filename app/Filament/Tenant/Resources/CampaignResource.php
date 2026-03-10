<?php
namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CampaignResource\Pages;
use Modules\Advertising\Models\Campaign;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\CreateAction;

class CampaignResource extends Resource {
    protected static ?string $model = Campaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $modelLabel = 'Ad Campaign (FZ-38)';

    public static function form(Form $form): Form {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('budget')->numeric()->prefix('RUB'),
            Select::make('vertical')->options([
                'hotel' => 'Hotel', 'restaurant' => 'Restaurant', 'taxi' => 'Taxi',
            ])->required(),
            TextInput::make('erid')->label('ORD Token (ERID)')->placeholder('Auto-generated or Manual')->disabled(),
            DatePicker::make('start_date'),
            DatePicker::make('end_date'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('vertical')->badge(),
            TextColumn::make('budget')->money('RUB')->sortable(),
            TextColumn::make('erid')->label('ERID')->copyable(),
            TextColumn::make('start_date')->date(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
