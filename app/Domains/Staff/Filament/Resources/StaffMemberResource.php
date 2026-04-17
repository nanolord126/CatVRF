<?php

declare(strict_types=1);

namespace App\Domains\Staff\Filament\Resources;

use App\Domains\Staff\Models\StaffMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMember::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Staff';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->maxLength(5000),
            Forms\Components\Select::make('status')
                ->options(['active' => 'Активный', 'draft' => 'Черновик', 'archived' => 'Архив'])
                ->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Staff\Filament\Resources\StaffMemberResource\Pages\ListStaffMembers::route('/'),
            'create' => \App\Domains\Staff\Filament\Resources\StaffMemberResource\Pages\CreateStaffMember::route('/create'),
            'edit' => \App\Domains\Staff\Filament\Resources\StaffMemberResource\Pages\EditStaffMember::route('/{record}/edit'),
        ];
    }
}
