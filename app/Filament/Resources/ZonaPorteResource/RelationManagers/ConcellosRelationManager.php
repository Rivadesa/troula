<?php

namespace App\Filament\Resources\ZonaPorteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ConcellosRelationManager extends RelationManager
{
    protected static string $relationship = 'concellos';

    protected static ?string $title = 'Concellos de esta zona';

    protected static ?string $modelLabel = 'concello';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('concello')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('concello')
            ->columns([
                Tables\Columns\TextColumn::make('concello')->searchable()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
