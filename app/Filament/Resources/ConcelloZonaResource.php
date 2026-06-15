<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConcelloZonaResource\Pages;
use App\Models\ConcelloZona;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConcelloZonaResource extends Resource
{
    protected static ?string $model = ConcelloZona::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Concellos';

    protected static ?string $modelLabel = 'concello';

    protected static ?string $pluralModelLabel = 'mapeo de concellos';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('concello')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\Select::make('zona_id')
                ->label('Zona de porte')
                ->relationship('zona', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('concello')
            ->columns([
                Tables\Columns\TextColumn::make('concello')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('zona.nombre')->label('Zona')->badge()->sortable(),
                Tables\Columns\TextColumn::make('zona.precio_porte')->money('EUR')->label('Porte'),
                Tables\Columns\TextColumn::make('zona.precio_montaje')->money('EUR')->label('Montaje'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zona_id')
                    ->label('Zona')
                    ->relationship('zona', 'nombre'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConcelloZonas::route('/'),
            'create' => Pages\CreateConcelloZona::route('/create'),
            'edit' => Pages\EditConcelloZona::route('/{record}/edit'),
        ];
    }
}
