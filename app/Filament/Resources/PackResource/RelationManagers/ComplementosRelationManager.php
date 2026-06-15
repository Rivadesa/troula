<?php

namespace App\Filament\Resources\PackResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Complementos incluidos en el pack (pivote pack_complemento con `cantidad`).
 */
class ComplementosRelationManager extends RelationManager
{
    protected static string $relationship = 'complementos';

    protected static ?string $title = 'Complementos incluidos';

    protected static ?string $modelLabel = 'complemento';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('cantidad')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría')->badge(),
                Tables\Columns\TextColumn::make('precio')->money('EUR')->label('Precio'),
                Tables\Columns\TextColumn::make('cantidad'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Incluir complemento')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()->label('Complemento'),
                        Forms\Components\TextInput::make('cantidad')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
