<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\TextInput::make('telefono')->label('Teléfono')->tel()->maxLength(255),
                Forms\Components\Toggle::make('acepto_lopd')->label('Consentimiento LOPD'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('telefono')->label('Teléfono')->searchable(),
                Tables\Columns\TextColumn::make('reservas_count')->counts('reservas')->label('Reservas')->badge(),
                Tables\Columns\IconColumn::make('acepto_lopd')->label('LOPD')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Alta')->date('d/m/Y')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('acepto_lopd')->label('Consentimiento LOPD'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Cliente')->schema([
                Infolists\Components\TextEntry::make('nombre'),
                Infolists\Components\TextEntry::make('email')->copyable(),
                Infolists\Components\TextEntry::make('telefono')->label('Teléfono'),
                Infolists\Components\IconEntry::make('acepto_lopd')->label('Consentimiento LOPD')->boolean(),
                Infolists\Components\TextEntry::make('consentimiento_en')->label('Fecha consentimiento')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])->columns(2),

            Infolists\Components\Section::make('Reservas')->schema([
                Infolists\Components\RepeatableEntry::make('reservas')
                    ->hiddenLabel()
                    ->schema([
                        Infolists\Components\TextEntry::make('referencia')->weight('bold'),
                        Infolists\Components\TextEntry::make('experiencia.nombre')->label('Experiencia'),
                        Infolists\Components\TextEntry::make('fecha_evento')->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('estado')->badge(),
                        Infolists\Components\TextEntry::make('total')->money('EUR'),
                    ])->columns(5),
            ])->visible(fn (Cliente $record): bool => $record->reservas()->exists()),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'view' => Pages\ViewCliente::route('/{record}'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
