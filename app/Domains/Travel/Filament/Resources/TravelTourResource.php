<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use App\Domains\Travel\Models\TravelTour;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\NumericColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class TravelTourResource extends Resource
{
    protected static ?string $model = TravelTour::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Travel';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Tour Information')
                ->columns(2)
                ->schema([
                    Select::make('agency_id')
                        ->relationship('agency', 'name')
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('destination')
                        ->required(),
                    TextInput::make('duration_days')
                        ->numeric()
                        ->required(),
                    DateInput::make('start_date')
                        ->required(),
                    DateInput::make('end_date')
                        ->required(),
                    TextInput::make('price')
                        ->numeric()
                        ->required(),
                    TextInput::make('cost_price')
                        ->numeric(),
                    TextInput::make('max_participants')
                        ->numeric()
                        ->required(),
                    TagsInput::make('tags'),
                    Textarea::make('description')
                        ->columnSpanFull(),
                    KeyValue::make('itinerary')
                        ->columnSpanFull(),
                    KeyValue::make('inclusions')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->searchable(),
                TextColumn::make('duration_days'),
                NumericColumn::make('price')
                    ->numeric(2),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('start_date')
                    ->date(),
                TextColumn::make('rating')
                    ->numeric(2),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'full' => 'Full',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }
}
