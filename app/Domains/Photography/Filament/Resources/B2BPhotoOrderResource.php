<?php declare(strict_types=1);

namespace App\Domains\Photography\Filament\Resources;

use Filament\Resources\Resource;

final class B2BPhotoOrderResource extends Resource
{

    protected static ?string $model = B2BPhotoOrder::class;

    	protected static ?string $navigationIcon = 'heroicon-o-document-check';

    	protected static ?string $navigationGroup = 'Photography B2B';

    	public static function form(Form $form): Form
    	{
    		return $form
    			->schema([
    				Forms\Components\Select::make('b2b_photo_storefront_id')
    					->relationship('storefront', 'company_name')
    					->required(),
    				Forms\Components\Select::make('photographer_id')
    					->relationship('photographer', 'full_name')
    					->required(),
    				Forms\Components\TextInput::make('company_contact_person')
    					->required(),
    				Forms\Components\TextInput::make('company_phone')
    					->tel()
    					->required(),
    				Forms\Components\DateTimePicker::make('datetime_start')
    					->required(),
    				Forms\Components\TextInput::make('duration_hours')
    					->numeric()
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
    						'approved' => 'Одобрен',
    						'rejected' => 'Отклонен',
    						'in_progress' => 'В процессе',
    						'completed' => 'Завершен',
    						'cancelled' => 'Отменен',
    					])
    					->required(),
    			]);
    	}

    	public static function table(Table $table): Table
    	{
    		return $table
    			->columns([
    				Tables\Columns\TextColumn::make('order_number')
    					->searchable(),
    				Tables\Columns\TextColumn::make('storefront.company_name'),
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
    						'approved' => 'Одобрен',
    						'rejected' => 'Отклонен',
    					]),
    			])
    			->actions([
    				Tables\Actions\ViewAction::make(),
    				Tables\Actions\EditAction::make(),
    			]);
    	}
}
