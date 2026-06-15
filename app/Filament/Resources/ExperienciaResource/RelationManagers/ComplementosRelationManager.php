<?php

namespace App\Filament\Resources\ExperienciaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Gestor de la relación N:N central: qué complementos ofrece la experiencia y con
 * qué reglas (precio_override, obligatorio, cantidad_maxima, orden). Los campos del
 * formulario que coinciden con columnas del pivote se guardan en `experiencia_complemento`.
 */
class ComplementosRelationManager extends RelationManager
{
    protected static string $relationship = 'complementos';

    protected static ?string $title = 'Complementos que ofrece';

    protected static ?string $modelLabel = 'complemento';

    /**
     * Esquema de edición del pivote (lo usa la acción Editar).
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('precio_override')
                ->label('Precio override')
                ->numeric()
                ->prefix('€')
                ->helperText('Si se indica, sustituye al precio del complemento para esta experiencia.'),
            Forms\Components\Toggle::make('obligatorio')
                ->helperText('Si entra de serie (preseleccionado en el configurador).'),
            Forms\Components\TextInput::make('cantidad_maxima')
                ->label('Cantidad máxima')
                ->numeric()
                ->default(1)
                ->minValue(1),
            Forms\Components\TextInput::make('orden')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('experiencia_complemento.orden')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría')->badge(),
                Tables\Columns\TextColumn::make('precio')->money('EUR')->label('Precio base'),
                Tables\Columns\TextColumn::make('precio_override')->money('EUR')->label('Override')->placeholder('—'),
                Tables\Columns\IconColumn::make('obligatorio')->boolean(),
                Tables\Columns\TextColumn::make('cantidad_maxima')->label('Cant. máx.'),
                Tables\Columns\TextColumn::make('orden'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asociar complemento')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Complemento'),
                        Forms\Components\TextInput::make('precio_override')
                            ->label('Precio override')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\Toggle::make('obligatorio'),
                        Forms\Components\TextInput::make('cantidad_maxima')
                            ->label('Cantidad máxima')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
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
