<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources;

use App\Domains\Sports\Fitness\Models\Membership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Членства';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Членство')->schema([
                Forms\Components\Select::make('gym_id')->label('Клуб')->relationship('gym', 'name')->required(),
                Forms\Components\Select::make('member_id')->label('Член')->relationship('member', 'email')->required(),
                Forms\Components\Select::make('type')->label('Тип')->options([
                    'monthly' => 'Месячное',
                    'quarterly' => 'Квартальное',
                    'annual' => 'Годовое',
                ])->required(),
            ]),
            Forms\Components\Section::make('Сумма')->schema([
                Forms\Components\TextInput::make('amount')->label('Сумма')->numeric(),
                Forms\Components\TextInput::make('commission_amount')->label('Комиссия (14%)')->numeric()->disabled(),
            ]),
            Forms\Components\Section::make('Сроки')->schema([
                Forms\Components\DateTimePickerInput::make('started_at')->label('Начало'),
                Forms\Components\DateTimePickerInput::make('expires_at')->label('Окончание'),
            ]),
            Forms\Components\Section::make('Статус')->schema([
                Forms\Components\Select::make('status')->label('Статус')->options([
                    'active' => 'Активно',
                    'inactive' => 'Неактивно',
                    'expired' => 'Истекло',
                    'cancelled' => 'Отменено',
                ]),
                Forms\Components\Toggle::make('auto_renewal')->label('Авто-продление'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gym.name')->label('Клуб')->searchable(),
                Tables\Columns\TextColumn::make('member.email')->label('Член'),
                Tables\Columns\TextColumn::make('type')->label('Тип')->badge(),
                Tables\Columns\TextColumn::make('amount')->label('Сумма')->money('RUB'),
                Tables\Columns\TextColumn::make('status')->label('Статус')->badge(),
                Tables\Columns\TextColumn::make('expires_at')->label('Окончание')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Активно',
                    'inactive' => 'Неактивно',
                    'expired' => 'Истекло',
                    'cancelled' => 'Отменено',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Sports\Fitness\Filament\Resources\MembershipResource\Pages\ListMemberships::route('/'),
            'create' => \App\Domains\Sports\Fitness\Filament\Resources\MembershipResource\Pages\CreateMembership::route('/create'),
            'edit' => \App\Domains\Sports\Fitness\Filament\Resources\MembershipResource\Pages\EditMembership::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}
