<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use Filament\Resources\Resource;

final class PetReviewResource extends Resource
{

    protected static ?string $model = PetReview::class;

        protected static ?string $slug = 'pet-reviews';

        protected static ?string $navigationIcon = 'heroicon-o-star';

        protected static ?string $navigationGroup = 'Pet Services';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Select::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->required(),
                    Select::make('vet_id')
                        ->relationship('vet', 'full_name'),
                    Select::make('reviewer_id')
                        ->relationship('reviewer', 'name')
                        ->required(),
                    TextInput::make('rating')
                        ->numeric()
                        ->min(1)
                        ->max(5)
                        ->required(),
                    Textarea::make('comment')
                        ->maxLength(1000),
                    Select::make('status')
                        ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                        ->default('pending'),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('reviewer.name')
                        ->searchable(),
                    TextColumn::make('clinic.name')
                        ->searchable(),
                    TextColumn::make('rating')
                        ->numeric(),
                    TextColumn::make('status')->badge()
                        ->colors([
                            'pending' => 'gray',
                            'approved' => 'success',
                            'rejected' => 'danger',
                        ]),
                    TextColumn::make('created_at')
                        ->dateTime(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    //
                ])
                ->bulkActions([
                    //
                ]);
        }
}
