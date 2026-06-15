<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaComplementoResource\Pages;
use App\Models\CategoriaComplemento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoriaComplementoResource extends Resource
{
    protected static ?string $model = CategoriaComplemento::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Categorías';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías de complemento';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Identificador único para URLs. Se genera a partir del nombre.'),
            Forms\Components\TextInput::make('orden')
                ->numeric()
                ->default(0)
                ->helperText('Orden de aparición en el configurador.'),
            Forms\Components\FileUpload::make('imagen')
                ->image()
                ->directory('categorias')
                ->imageEditor(),
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
                Tables\Columns\TextColumn::make('slug')->searchable()->color('gray'),
                Tables\Columns\TextColumn::make('complementos_count')->counts('complementos')->label('Complementos')->badge(),
                Tables\Columns\TextColumn::make('orden')->sortable(),
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
            'index' => Pages\ListCategoriaComplementos::route('/'),
            'create' => Pages\CreateCategoriaComplemento::route('/create'),
            'edit' => Pages\EditCategoriaComplemento::route('/{record}/edit'),
        ];
    }
}
