<?php

namespace App\Filament\Resources\PackResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Máquinas base intercambiables del pack (pivote `pack_experiencia` con `suplemento`).
 *
 * El cliente puede cambiar en el configurador el fotomatón incluido en el pack
 * pagando el suplemento indicado aquí. La base por defecto del pack
 * (`packs.experiencia_id`) se ofrece siempre sin suplemento aunque no se liste.
 */
class BasesRelationManager extends RelationManager
{
    protected static string $relationship = 'basesDisponibles';

    protected static ?string $title = 'Máquinas base alternativas';

    protected static ?string $modelLabel = 'máquina base';

    protected static ?string $pluralModelLabel = 'máquinas base';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('suplemento')
                ->label('Suplemento')
                ->numeric()
                ->prefix('€')
                ->default(0)
                ->minValue(0)
                ->required()
                ->helperText('Lo que se suma al precio del pack si el cliente elige esta máquina.'),
            Forms\Components\TextInput::make('orden')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('pack_experiencia.orden')
            ->description('La máquina base por defecto del pack se ofrece siempre sin suplemento; aquí se añaden las alternativas.')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->square(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('precio_base')->money('EUR')->label('Precio suelto'),
                Tables\Columns\TextColumn::make('suplemento')->money('EUR')->label('Suplemento'),
                Tables\Columns\TextColumn::make('orden'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Añadir máquina base')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()->label('Experiencia'),
                        Forms\Components\TextInput::make('suplemento')
                            ->label('Suplemento')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        Forms\Components\TextInput::make('orden')
                            ->numeric()
                            ->default(0),
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
