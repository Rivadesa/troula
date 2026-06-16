<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloAdministradores;
use App\Models\Configuracion;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

/**
 * @property-read Form $form
 */
class ConfiguracionCorreo extends Page implements HasForms
{
    use InteractsWithForms;
    use SoloAdministradores;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Correo';

    protected static ?string $title = 'Configuración de correo (SMTP)';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.configuracion-correo';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $datos = Configuracion::actual()->attributesToArray();
        unset($datos['mail_password']); // no se precarga la contraseña

        $this->form->fill($datos);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Envío de correo')
                    ->description('Mientras esté en «Desactivado», los avisos solo se registran en el log y no se envían.')
                    ->schema([
                        Forms\Components\Select::make('mail_mailer')
                            ->label('Modo de envío')
                            ->options([
                                'log' => 'Desactivado (solo registro)',
                                'smtp' => 'SMTP',
                            ])
                            ->default('log')
                            ->live()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Servidor SMTP')
                    ->visible(fn (Get $get): bool => $get('mail_mailer') === 'smtp')
                    ->schema([
                        Forms\Components\TextInput::make('mail_host')->label('Servidor (host)')->maxLength(255),
                        Forms\Components\TextInput::make('mail_port')->label('Puerto')->numeric()->default(587),
                        Forms\Components\TextInput::make('mail_username')->label('Usuario')->maxLength(255),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->helperText('Déjala en blanco para mantener la actual. Se guarda cifrada.'),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Cifrado')
                            ->options(['tls' => 'TLS (587)', 'ssl' => 'SSL (465)'])
                            ->placeholder('Ninguno'),
                    ])->columns(2),

                Forms\Components\Section::make('Remitente y destinatario')
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')->label('Remite (from)')->email()->maxLength(255),
                        Forms\Components\TextInput::make('mail_from_name')->label('Nombre del remitente')->maxLength(255),
                        Forms\Components\TextInput::make('mail_admin_address')
                            ->label('Destinatario de los avisos de reserva')
                            ->email()
                            ->maxLength(255)
                            ->helperText('A esta dirección llegan los leads del configurador.'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        Configuracion::actual()->update($this->form->getState());

        Notification::make()->title('Configuración de correo guardada')->success()->send();
    }

    public function enviarPrueba(): void
    {
        $this->guardar();

        $cfg = Configuracion::actual()->fresh();
        $cfg->aplicarCorreo();

        if (($cfg->mail_mailer ?: 'log') !== 'smtp') {
            Notification::make()
                ->title('Estás en modo «Desactivado»')
                ->body('Cambia el modo a SMTP y rellena los datos para enviar correo de verdad.')
                ->warning()
                ->send();

            return;
        }

        $destino = $cfg->mail_admin_address ?: $cfg->email ?: config('mail.from.address');

        try {
            Mail::raw(
                'Email de prueba de '.$cfg->nombre.'. Si recibes este mensaje, la configuración SMTP funciona correctamente.',
                fn ($message) => $message->to($destino)->subject('Prueba de correo · '.$cfg->nombre),
            );

            Notification::make()->title('Email de prueba enviado a '.$destino)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo enviar el email de prueba')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')->label('Guardar')->submit('guardar'),
            Action::make('enviarPrueba')
                ->label('Enviar email de prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('enviarPrueba'),
        ];
    }
}
