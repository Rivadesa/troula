<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\ConcelloZonaResource\Pages;
use App\Models\ConcelloZona;
use App\Models\ZonaPorte;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ConcelloZonaResource extends Resource
{
    use SoloAdministradores;

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
            Forms\Components\Select::make('provincia')
                ->options(array_combine(ConcelloZona::PROVINCIAS, ConcelloZona::PROVINCIAS))
                ->native(false)
                ->required(),
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
            ->groups([
                Tables\Grouping\Group::make('provincia')->collapsible(),
            ])
            ->defaultGroup('provincia')
            ->columns([
                Tables\Columns\TextColumn::make('concello')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provincia')->badge()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('zona.nombre')->label('Zona')->badge()->sortable(),
                Tables\Columns\TextColumn::make('zona.precio_porte')->money('EUR')->label('Porte'),
                Tables\Columns\TextColumn::make('zona.precio_montaje')->money('EUR')->label('Montaje'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provincia')
                    ->options(array_combine(ConcelloZona::PROVINCIAS, ConcelloZona::PROVINCIAS)),
                Tables\Filters\SelectFilter::make('zona_id')
                    ->label('Zona')
                    ->relationship('zona', 'nombre'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Con 313 concellos, reasignar zona uno a uno sería inviable:
                    // se filtra por provincia, se seleccionan y se cambian de golpe.
                    Tables\Actions\BulkAction::make('asignar_zona')
                        ->label('Asignar zona de porte')
                        ->icon('heroicon-o-map')
                        ->form([
                            // Sin ->relationship(): en un bulk action no hay registro dueño.
                            Forms\Components\Select::make('zona_id')
                                ->label('Zona de porte')
                                ->options(fn (): array => ZonaPorte::orderBy('nombre')->pluck('nombre', 'id')->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records
                            ->each(fn (ConcelloZona $registro) => $registro->update(['zona_id' => $data['zona_id']])))
                        ->deselectRecordsAfterCompletion(),
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
