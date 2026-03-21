<?php declare(strict_types=1);

namespace App\Domains\Freelance\Filament\Resources;

use App\Domains\Freelance\Models\FreelanceContract;
use Filament\Forms\Components\DateTimeInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class FreelanceContractResource extends Resource
{
    protected static ?string $model = FreelanceContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Freelance';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Contract Details')->schema([
                TextInput::make('contract_number')->disabled(),
                TextInput::make('agreed_amount')->numeric()->step(0.01)->disabled(),
                TextInput::make('commission_amount')->numeric()->step(0.01)->disabled(),
                TextInput::make('duration_days')->numeric(),
            ]),

            Section::make('Parties')->schema([
                TextInput::make('freelancer_id')->disabled(),
                TextInput::make('client_id')->disabled(),
            ]),

            Section::make('Timeline')->schema([
                DateTimeInput::make('start_date'),
                DateTimeInput::make('end_date')->nullable(),
                DateTimeInput::make('completed_at')->nullable(),
            ]),

            Section::make('Payment & Status')->schema([
                TextInput::make('status')->readonly(),
                TextInput::make('amount_paid')->numeric()->step(0.01)->disabled(),
                TextInput::make('amount_held_escrow')->numeric()->step(0.01)->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_number')->searchable()->sortable(),
                TextColumn::make('freelancer.full_name')->label('Freelancer'),
                TextColumn::make('client.name')->label('Client'),
                TextColumn::make('agreed_amount')->money('RUB'),
                TextColumn::make('amount_paid')->money('RUB'),
                TextColumn::make('status')->sortable(),
                TextColumn::make('start_date')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'on_hold' => 'On Hold',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'disputed' => 'Disputed',
                ]),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
