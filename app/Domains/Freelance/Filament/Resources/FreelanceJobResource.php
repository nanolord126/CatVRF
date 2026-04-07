<?php declare(strict_types=1);

namespace App\Domains\Freelance\Filament\Resources;

use Filament\Resources\Resource;

final class FreelanceJobResource extends Resource
{

    protected static ?string $model = FreelanceJob::class;

        protected static ?string $navigationIcon = 'heroicon-o-document-text';

        protected static ?string $navigationGroup = 'Freelance';

        protected static ?int $navigationSort = 2;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Job Details')->schema([
                    TextInput::make('title')->required(),
                    Textarea::make('description')->required()->rows(4),
                    TagsInput::make('categories')->placeholder('Add categories'),
                    TagsInput::make('skills_required')->placeholder('Required skills'),
                ]),

                Section::make('Budget & Timeline')->schema([
                    TextInput::make('budget_min')->numeric()->step(0.01),
                    TextInput::make('budget_max')->numeric()->step(0.01),
                    TextInput::make('duration_days')->numeric()->minValue(1),
                    DateTimeInput::make('deadline'),
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
                    TextColumn::make('client.name')->label('Client'),
                    TextColumn::make('budget_min')->money('RUB'),
                    TextColumn::make('budget_max')->money('RUB'),
                    TextColumn::make('status')->sortable(),
                    TextColumn::make('proposals_count')->sortable(),
                    TextColumn::make('posted_at')->dateTime()->sortable(),
                ])
                ->filters([
                    SelectFilter::make('status')->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),
                ])
                ->actions([EditAction::make()])
                ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }
}
