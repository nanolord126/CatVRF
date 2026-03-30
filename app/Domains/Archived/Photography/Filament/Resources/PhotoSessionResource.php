<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PhotoSessionResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = PhotoSession::class;


    	protected static ?string $navigationIcon = 'heroicon-o-calendar';


    	protected static ?string $navigationGroup = 'Photography';


    	public static function form(Form $form): Form


    	{


    		return $form


    			->schema([


    				Forms\Components\Select::make('photo_studio_id')


    					->relationship('studio', 'name')


    					->required(),


    				Forms\Components\Select::make('photographer_id')


    					->relationship('photographer', 'full_name')


    					->required(),


    				Forms\Components\Select::make('photo_package_id')


    					->relationship('package', 'name')


    					->required(),


    				Forms\Components\DateTimePicker::make('datetime_start')


    					->required(),


    				Forms\Components\DateTimePicker::make('datetime_end')


    					->required(),


    				Forms\Components\TextInput::make('total_amount')


    					->numeric()


    					->required(),


    				Forms\Components\TextInput::make('commission_amount')


    					->numeric()


    					->disabled(),


    				Forms\Components\Select::make('status')


    					->options([


    						'pending' => 'Ожидание',


    						'confirmed' => 'Подтверждена',


    						'in_progress' => 'В процессе',


    						'completed' => 'Завершена',


    						'cancelled' => 'Отменена',


    					])


    					->required(),


    			]);


    	}


    	public static function table(Table $table): Table


    	{


    		return $table


    			->columns([


    				Tables\Columns\TextColumn::make('session_number')


    					->searchable(),


    				Tables\Columns\TextColumn::make('studio.name'),


    				Tables\Columns\TextColumn::make('photographer.full_name'),


    				Tables\Columns\TextColumn::make('total_amount')


    					->numeric(2),


    				Tables\Columns\TextColumn::make('status')


    					->badge(),


    				Tables\Columns\TextColumn::make('datetime_start')


    					->dateTime(),


    			])


    			->filters([


    				Tables\Filters\SelectFilter::make('status')


    					->options([


    						'pending' => 'Ожидание',


    						'confirmed' => 'Подтверждена',


    						'in_progress' => 'В процессе',


    						'completed' => 'Завершена',


    					]),


    			])


    			->actions([


    				Tables\Actions\ViewAction::make(),


    				Tables\Actions\EditAction::make(),


    			]);


    	}
}
