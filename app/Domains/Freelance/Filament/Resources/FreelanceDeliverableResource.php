<?php declare(strict_types=1);

namespace App\Domains\Freelance\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceDeliverableResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FreelanceDeliverable::class;

        protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

        protected static ?string $navigationGroup = 'Freelance';

        protected static ?int $navigationSort = 4;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Deliverable Details')->schema([
                    TextInput::make('title')->required(),
                    Textarea::make('description')->rows(3),
                    TextInput::make('revision_count')->numeric()->disabled(),
                ]),

                Section::make('Timeline')->schema([
                    DateTimeInput::make('submitted_at'),
                    DateTimeInput::make('approved_at')->nullable(),
                ]),

                Section::make('Feedback')->schema([
                    Textarea::make('revision_notes')->rows(3),
                ]),

                Section::make('Status')->schema([
                    TextInput::make('status')->readonly(),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('title')->searchable()->sortable(),
                    TextColumn::make('contract.contract_number')->label('Contract'),
                    TextColumn::make('freelancer.full_name')->label('Freelancer'),
                    TextColumn::make('status')->sortable(),
                    TextColumn::make('revision_count')->sortable(),
                    TextColumn::make('submitted_at')->dateTime()->sortable(),
                    TextColumn::make('approved_at')->dateTime()->nullable(),
                ])
                ->filters([
                    SelectFilter::make('status')->options([
                        'pending' => 'Pending',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'revisions_requested' => 'Revisions Requested',
                        'rejected' => 'Rejected',
                    ]),
                ])
                ->actions([EditAction::make()])
                ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }
}
