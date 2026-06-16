<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\PackResource\Pages;
use App\Filament\Resources\PackResource\RelationManagers\ComplementosRelationManager;
use App\Models\Pack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PackResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Pack::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Packs';

    protected static ?string $modelLabel = 'pack';

    protected static ?string $pluralModelLabel = 'packs';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('experiencia_id')
                    ->label('Experiencia')
                    ->relationship('experiencia', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('precio')
                    ->label('Precio cerrado')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->default(0)
                    ->helperText('Precio del pack (normalmente más barato que la suma de las partes).'),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('imagen')
                    ->image()
                    ->directory('packs')
                    ->imageEditor()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->square(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('experiencia.nombre')->label('Experiencia')->badge()->sortable(),
                Tables\Columns\TextColumn::make('precio')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('complementos_count')->counts('complementos')->label('Incluye')->badge(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('experiencia_id')
                    ->label('Experiencia')
                    ->relationship('experiencia', 'nombre'),
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

    public static function getRelations(): array
    {
        return [
            ComplementosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPacks::route('/'),
            'create' => Pages\CreatePack::route('/create'),
            'edit' => Pages\EditPack::route('/{record}/edit'),
        ];
    }
}
