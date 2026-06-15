<?php

namespace App\Filament\Resources;

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use App\Filament\Resources\ReservaResource\Pages;
use App\Models\Reserva;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservaResource extends Resource
{
    protected static ?string $model = Reserva::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?string $navigationLabel = 'Reservas';

    protected static ?string $modelLabel = 'reserva';

    protected static ?string $pluralModelLabel = 'reservas';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('estado', EstadoReserva::Solicitada)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Los empleados ven las reservas en modo lectura; solo los admins gestionan.
    public static function canCreate(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Estado')->schema([
                Forms\Components\TextInput::make('referencia')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('estado')
                    ->options(EstadoReserva::class)
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('Cliente')->schema([
                Forms\Components\TextInput::make('cliente_nombre')->label('Nombre')->required(),
                Forms\Components\TextInput::make('cliente_email')->label('Email')->email()->required(),
                Forms\Components\TextInput::make('cliente_telefono')->label('Teléfono')->required(),
            ])->columns(3),

            Forms\Components\Section::make('Evento')->schema([
                Forms\Components\Select::make('experiencia_id')
                    ->label('Experiencia')
                    ->relationship('experiencia', 'nombre')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('pack_id')
                    ->label('Pack')
                    ->relationship('pack', 'nombre')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DatePicker::make('fecha_evento')->label('Fecha')->required(),
                Forms\Components\Select::make('turno')->options(Turno::class)->required(),
                Forms\Components\TextInput::make('concello')->required(),
                Forms\Components\TextInput::make('lugar_evento')->label('Lugar'),
                Forms\Components\Textarea::make('observaciones')->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Importes (congelados)')
                ->description('Estos importes se fijaron al crear la reserva y no se recalculan.')
                ->schema([
                    Forms\Components\TextInput::make('subtotal')->prefix('€')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('ajuste_temporada')->prefix('€')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('total_complementos')->prefix('€')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('porte')->prefix('€')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('montaje')->prefix('€')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('total')->prefix('€')->disabled()->dehydrated(false),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_evento')
            ->columns([
                Tables\Columns\TextColumn::make('referencia')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('cliente_nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('experiencia.nombre')->label('Experiencia')->badge(),
                Tables\Columns\TextColumn::make('fecha_evento')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('turno')->badge(),
                Tables\Columns\TextColumn::make('concello')->toggleable(),
                Tables\Columns\TextColumn::make('total')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('estado')->badge()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(EstadoReserva::class)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('experiencia_id')
                    ->label('Experiencia')
                    ->relationship('experiencia', 'nombre'),
                Tables\Filters\Filter::make('fecha_evento')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha_evento', '>=', $fecha))
                            ->when($data['hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha_evento', '<=', $fecha));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('cambiarEstado')
                    ->label('Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => auth()->user()?->esAdmin() ?? false)
                    ->fillForm(fn (Reserva $record): array => ['estado' => $record->estado])
                    ->form([
                        Forms\Components\Select::make('estado')
                            ->options(EstadoReserva::class)
                            ->required(),
                    ])
                    ->action(fn (Reserva $record, array $data) => $record->update(['estado' => $data['estado']])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->esAdmin() ?? false),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Reserva')->schema([
                Infolists\Components\TextEntry::make('referencia')->weight('bold'),
                Infolists\Components\TextEntry::make('estado')->badge(),
                Infolists\Components\TextEntry::make('cliente_nombre')->label('Cliente'),
                Infolists\Components\TextEntry::make('cliente_email')->label('Email')->copyable(),
                Infolists\Components\TextEntry::make('cliente_telefono')->label('Teléfono'),
            ])->columns(3),

            Infolists\Components\Section::make('Evento')->schema([
                Infolists\Components\TextEntry::make('experiencia.nombre')->label('Experiencia'),
                Infolists\Components\TextEntry::make('pack.nombre')->label('Pack')->placeholder('— a la carta —'),
                Infolists\Components\TextEntry::make('fecha_evento')->date('d/m/Y'),
                Infolists\Components\TextEntry::make('turno')->badge(),
                Infolists\Components\TextEntry::make('concello'),
                Infolists\Components\TextEntry::make('zona.nombre')->label('Zona de porte')->placeholder('—'),
                Infolists\Components\TextEntry::make('lugar_evento')->label('Lugar')->placeholder('—'),
                Infolists\Components\TextEntry::make('observaciones')->placeholder('—')->columnSpanFull(),
            ])->columns(3),

            Infolists\Components\Section::make('Complementos')->schema([
                Infolists\Components\RepeatableEntry::make('complementos')
                    ->hiddenLabel()
                    ->schema([
                        Infolists\Components\TextEntry::make('nombre'),
                        Infolists\Components\TextEntry::make('pivot.cantidad')->label('Cantidad'),
                        Infolists\Components\TextEntry::make('pivot.precio_congelado')->label('Precio congelado')->money('EUR'),
                    ])->columns(3),
            ])->visible(fn (Reserva $record): bool => $record->complementos->isNotEmpty()),

            Infolists\Components\Section::make('Desglose (congelado)')->schema([
                Infolists\Components\TextEntry::make('subtotal')->money('EUR'),
                Infolists\Components\TextEntry::make('ajuste_temporada')->label('Ajuste temporada')->money('EUR'),
                Infolists\Components\TextEntry::make('total_complementos')->label('Complementos')->money('EUR'),
                Infolists\Components\TextEntry::make('porte')->money('EUR'),
                Infolists\Components\TextEntry::make('montaje')->money('EUR'),
                Infolists\Components\TextEntry::make('total')->money('EUR')->weight('bold')->size('lg'),
            ])->columns(3),

            Infolists\Components\Section::make('Pagos')->schema([
                Infolists\Components\RepeatableEntry::make('pagos')
                    ->hiddenLabel()
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo')->badge(),
                        Infolists\Components\TextEntry::make('importe')->money('EUR'),
                        Infolists\Components\TextEntry::make('estado')->badge(),
                        Infolists\Components\TextEntry::make('pagado_en')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ])->columns(4),
            ])->visible(fn (Reserva $record): bool => $record->pagos->isNotEmpty()),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservas::route('/'),
            'create' => Pages\CreateReserva::route('/create'),
            'view' => Pages\ViewReserva::route('/{record}'),
            'edit' => Pages\EditReserva::route('/{record}/edit'),
        ];
    }
}
