<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\ZonaPorteResource\Pages;
use App\Filament\Resources\ZonaPorteResource\RelationManagers\ConcellosRelationManager;
use App\Models\ZonaPorte;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ZonaPorteResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = ZonaPorte::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Zonas de porte';

    protected static ?string $modelLabel = 'zona de porte';

    protected static ?string $pluralModelLabel = 'zonas de porte';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('precio_porte')
                ->label('Precio de porte')
                ->numeric()
                ->prefix('€')
                ->required()
                ->default(0),
            Forms\Components\TextInput::make('precio_montaje')
                ->label('Precio de montaje')
                ->numeric()
                ->prefix('€')
                ->required()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('precio_porte')->money('EUR')->label('Porte'),
                Tables\Columns\TextColumn::make('precio_montaje')->money('EUR')->label('Montaje'),
                Tables\Columns\TextColumn::make('concellos_count')->counts('concellos')->label('Concellos')->badge(),
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

    public static function getRelations(): array
    {
        return [
            ConcellosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZonaPortes::route('/'),
            'create' => Pages\CreateZonaPorte::route('/create'),
            'edit' => Pages\EditZonaPorte::route('/{record}/edit'),
        ];
    }
}
