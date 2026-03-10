<?php

namespace App\Filament\Pages;

use App\Models\ActiveDevice;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ActiveDevices extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string $view = 'filament.pages.active-devices';

    public function table(Table $table): Table {
        return $table->query(ActiveDevice::where('user_id', auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('ip')->label('IP Address'),
                Tables\Columns\TextColumn::make('browser')->label('Browser'),
                Tables\Columns\TextColumn::make('location')->label('Location'),
                Tables\Columns\TextColumn::make('last_active_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label('Logout Device')
                    ->after(fn($record) => session()->getHandler()->destroy($record->session_id)),
            ]);
    }
}
