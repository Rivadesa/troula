<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\ComplementoResource\Pages;
use App\Models\Complemento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ComplementoResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Complemento::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Complementos';

    protected static ?string $modelLabel = 'complemento';

    protected static ?string $pluralModelLabel = 'complementos';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
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
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('precio')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->default(0)
                    ->helperText('Precio base; cada experiencia puede sobreescribirlo con un precio_override.'),
                Forms\Components\FileUpload::make('imagen')
                    ->image()
                    ->directory('complementos')
                    ->imageEditor(),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->circular(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría')->badge()->sortable(),
                Tables\Columns\TextColumn::make('precio')->money('EUR')->sortable(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre'),
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
            'index' => Pages\ListComplementos::route('/'),
            'create' => Pages\CreateComplemento::route('/create'),
            'edit' => Pages\EditComplemento::route('/{record}/edit'),
        ];
    }
}
