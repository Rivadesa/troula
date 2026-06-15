<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExperienciaResource\Pages;
use App\Filament\Resources\ExperienciaResource\RelationManagers\ComplementosRelationManager;
use App\Models\Experiencia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExperienciaResource extends Resource
{
    protected static ?string $model = Experiencia::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Experiencias';

    protected static ?string $modelLabel = 'experiencia';

    protected static ?string $pluralModelLabel = 'experiencias';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la experiencia')->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('precio_base')
                    ->label('Precio base')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->default(0),
                Forms\Components\FileUpload::make('imagen')
                    ->image()
                    ->directory('experiencias')
                    ->imageEditor(),
            ])->columns(2),

            Forms\Components\Section::make('Disponibilidad y orden')->schema([
                Forms\Components\TextInput::make('unidades')
                    ->numeric()
                    ->minValue(0)
                    ->default(1)
                    ->required()
                    ->helperText('Nº de unidades físicas disponibles.'),
                Forms\Components\Toggle::make('permite_turnos')
                    ->label('Permite turnos (mañana / tarde)')
                    ->helperText('Si está desactivado, cada reserva ocupa el día completo.'),
                Forms\Components\TextInput::make('orden')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->reorderable('orden')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->square(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('precio_base')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('unidades')->badge(),
                Tables\Columns\IconColumn::make('permite_turnos')->boolean()->label('Turnos'),
                Tables\Columns\TextColumn::make('complementos_count')->counts('complementos')->label('Complementos')->badge(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo'),
                Tables\Filters\TernaryFilter::make('permite_turnos')->label('Permite turnos'),
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
            ComplementosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExperiencias::route('/'),
            'create' => Pages\CreateExperiencia::route('/create'),
            'edit' => Pages\EditExperiencia::route('/{record}/edit'),
        ];
    }
}
