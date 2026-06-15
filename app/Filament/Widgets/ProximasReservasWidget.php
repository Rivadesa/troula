<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ReservaResource;
use App\Models\Reserva;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProximasReservasWidget extends BaseWidget
{
    protected static ?string $heading = 'Próximas reservas';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Reserva::query()
                    ->activas()
                    ->whereDate('fecha_evento', '>=', today())
                    ->orderBy('fecha_evento')
            )
            ->columns([
                Tables\Columns\TextColumn::make('fecha_evento')->date('d/m/Y')->label('Fecha')->sortable(),
                Tables\Columns\TextColumn::make('turno')->badge(),
                Tables\Columns\TextColumn::make('referencia')->searchable(),
                Tables\Columns\TextColumn::make('cliente_nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('experiencia.nombre')->label('Experiencia')->badge(),
                Tables\Columns\TextColumn::make('concello')->toggleable(),
                Tables\Columns\TextColumn::make('total')->money('EUR'),
                Tables\Columns\TextColumn::make('estado')->badge(),
            ])
            ->recordUrl(fn (Reserva $record): string => ReservaResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }
}
