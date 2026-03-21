<?php declare(strict_types=1);

namespace App\Domains\Freelance\Filament\Resources;

use App\Domains\Freelance\Models\FreelanceReview;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class FreelanceReviewResource extends Resource
{
    protected static ?string $model = FreelanceReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Freelance';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Review Details')->schema([
                TextInput::make('reviewer_id')->disabled(),
                TextInput::make('overall_rating')->numeric()->min(1)->max(5),
                TextInput::make('communication_rating')->numeric()->min(1)->max(5)->nullable(),
                TextInput::make('work_quality_rating')->numeric()->min(1)->max(5)->nullable(),
                TextInput::make('timeliness_rating')->numeric()->min(1)->max(5)->nullable(),
            ]),

            Section::make('Comment')->schema([
                Textarea::make('comment')->rows(4),
            ]),

            Section::make('Engagement')->schema([
                TextInput::make('helpful_count')->numeric()->disabled(),
                TextInput::make('unhelpful_count')->numeric()->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('freelancer.full_name')->label('Freelancer'),
                TextColumn::make('overall_rating')->sortable(),
                TextColumn::make('communication_rating'),
                TextColumn::make('work_quality_rating'),
                TextColumn::make('review_type')->sortable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('helpful_count'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
                SelectFilter::make('overall_rating')->options([
                    '5' => '5 Stars',
                    '4' => '4 Stars',
                    '3' => '3 Stars',
                    '2' => '2 Stars',
                    '1' => '1 Star',
                ]),
            ])
            ->actions([])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
