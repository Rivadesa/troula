<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloAdministradores;
use App\Models\Configuracion;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property-read Form $form
 */
class ConfiguracionEmpresa extends Page implements HasForms
{
    use InteractsWithForms;
    use SoloAdministradores;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Empresa';

    protected static ?string $title = 'Configuración de la empresa';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.configuracion-empresa';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Configuracion::actual()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                        Forms\Components\TextInput::make('eslogan')->label('Eslogan / lema')->maxLength(255),
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('empresa')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
                        Forms\Components\TextInput::make('telefono')->label('Teléfono')->tel()->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(255),
                        Forms\Components\TextInput::make('web')->label('Sitio web')->url()->prefix('https://')->maxLength(255),
                        Forms\Components\TextInput::make('politica_privacidad_url')
                            ->label('URL política de privacidad')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Se enlaza en el checkbox de LOPD del configurador.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Dirección y datos fiscales')
                    ->schema([
                        Forms\Components\TextInput::make('direccion')->label('Dirección')->maxLength(255)->columnSpanFull(),
                        Forms\Components\TextInput::make('ciudad')->label('Ciudad')->maxLength(255),
                        Forms\Components\TextInput::make('codigo_postal')->label('Código postal')->maxLength(255),
                        Forms\Components\TextInput::make('cif')->label('CIF / NIF')->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Redes sociales')
                    ->description('URL completa de cada perfil.')
                    ->schema([
                        Forms\Components\TextInput::make('instagram')->url()->prefix('https://')->maxLength(255),
                        Forms\Components\TextInput::make('facebook')->url()->prefix('https://')->maxLength(255),
                        Forms\Components\TextInput::make('tiktok')->label('TikTok')->url()->prefix('https://')->maxLength(255),
                        Forms\Components\TextInput::make('youtube')->label('YouTube')->url()->prefix('https://')->maxLength(255),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        Configuracion::actual()->update($datos);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->submit('guardar'),
        ];
    }
}
