<?php declare(strict_types=1);

namespace App\Domains\Freelance\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelancerResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Freelancer::class;

        protected static ?string $navigationIcon = 'heroicon-o-briefcase';

        protected static ?string $navigationGroup = 'Freelance';

        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Profile Information')->schema([
                    TextInput::make('full_name')->required(),
                    Textarea::make('bio')->rows(4),
                    TextInput::make('hourly_rate')->numeric()->step(0.01),
                    TagsInput::make('skills')->placeholder('Add skills'),
                    TagsInput::make('languages')->placeholder('Add languages'),
                    TextInput::make('experience_years')->numeric()->minValue(0),
                ]),

                Section::make('Links')->schema([
                    TextInput::make('portfolio_url')->url(),
                    TextInput::make('website')->url(),
                ]),

                Section::make('Status')->schema([
                    Toggle::make('is_verified')->label('Verified'),
                    Toggle::make('is_active')->label('Active'),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('full_name')->searchable()->sortable(),
                    TextColumn::make('user.name')->label('User'),
                    TextColumn::make('rating')->sortable(),
                    TextColumn::make('review_count')->sortable(),
                    TextColumn::make('jobs_completed')->sortable(),
                    TextColumn::make('hourly_rate')->money('RUB'),
                    ToggleColumn::make('is_verified'),
                    ToggleColumn::make('is_active'),
                ])
                ->filters([])
                ->actions([EditAction::make()])
                ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }
}
