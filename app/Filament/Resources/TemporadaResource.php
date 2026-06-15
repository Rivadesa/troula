<?php

namespace App\Filament\Resources;

use App\Enums\TipoAjuste;
use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\TemporadaResource\Pages;
use App\Models\Temporada;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemporadaResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Temporada::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Temporadas';

    protected static ?string $modelLabel = 'temporada';

    protected static ?string $pluralModelLabel = 'temporadas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\DatePicker::make('fecha_inicio')
                ->label('Fecha de inicio')
                ->required()
                ->helperText('El rango se repite cada año (se compara por mes y día).'),
            Forms\Components\DatePicker::make('fecha_fin')
                ->label('Fecha de fin')
                ->required(),
            Forms\Components\Select::make('tipo_ajuste')
                ->label('Tipo de ajuste')
                ->options(TipoAjuste::class)
                ->default(TipoAjuste::Porcentaje)
                ->required(),
            Forms\Components\TextInput::make('valor')
                ->numeric()
                ->required()
                ->default(0)
                ->helperText('Positivo = recargo, negativo = descuento.'),
            Forms\Components\Toggle::make('activo')
                ->default(true)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fecha_inicio')->date('d/m')->label('Desde'),
                Tables\Columns\TextColumn::make('fecha_fin')->date('d/m')->label('Hasta'),
                Tables\Columns\TextColumn::make('tipo_ajuste')->label('Ajuste')->badge(),
                Tables\Columns\TextColumn::make('valor')->label('Valor'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo'),
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
            'index' => Pages\ListTemporadas::route('/'),
            'create' => Pages\CreateTemporada::route('/create'),
            'edit' => Pages\EditTemporada::route('/{record}/edit'),
        ];
    }
}
