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

/**
 * Señal que se pide al reservar y métodos de cobro (transferencia y TPV Redsys).
 *
 * @property-read Form $form
 */
class ConfiguracionPagos extends Page implements HasForms
{
    use InteractsWithForms;
    use SoloAdministradores;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $title = 'Señal y cobros';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.configuracion-pagos';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $datos = Configuracion::actual()->attributesToArray();
        unset($datos['redsys_clave']);   // la clave no se precarga en el formulario

        $this->form->fill($datos);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Señal')
                    ->description('Importe que se pide al cliente al hacer la reserva. El resto se abona antes del evento.')
                    ->schema([
                        Forms\Components\Select::make('senal_tipo')
                            ->label('Tipo')
                            ->options(['porcentaje' => 'Porcentaje del total', 'fijo' => 'Importe fijo'])
                            ->default('porcentaje')
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('senal_valor')
                            ->label(fn (Get $get): string => $get('senal_tipo') === 'fijo' ? 'Importe' : 'Porcentaje')
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Get $get): string => $get('senal_tipo') === 'fijo' ? '€' : '%')
                            ->required()
                            ->helperText('Pon 0 para no pedir señal: la reserva se creará sin cobro.'),
                        Forms\Components\TextInput::make('reserva_minutos_retencion')
                            ->label('Minutos que se retiene la fecha')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('min')
                            ->required()
                            ->helperText('Tiempo que el cliente tiene para firmar y pagar. Si no paga, la fecha vuelve a quedar libre. 0 = la reserva bloquea la fecha desde el primer momento.'),
                        Forms\Components\Textarea::make('senal_texto')
                            ->label('Aviso para el cliente')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('P. ej.: la señal no se devuelve en caso de cancelación con menos de 15 días.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Transferencia bancaria')
                    ->schema([
                        Forms\Components\Toggle::make('pago_transferencia')
                            ->label('Aceptar transferencia')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('pago_iban')
                            ->label('IBAN')
                            ->maxLength(40)
                            ->visible(fn (Get $get): bool => (bool) $get('pago_transferencia')),
                        Forms\Components\TextInput::make('pago_titular')
                            ->label('Titular de la cuenta')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => (bool) $get('pago_transferencia')),
                    ])->columns(2),

                Forms\Components\Section::make('Pago con tarjeta (TPV Redsys)')
                    ->description('Datos que facilita el banco al contratar el TPV Virtual. Mientras el entorno sea «Pruebas» no se cobra dinero real.')
                    ->schema([
                        Forms\Components\Toggle::make('pago_tarjeta')
                            ->label('Aceptar pago con tarjeta')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('redsys_entorno')
                            ->label('Entorno')
                            ->options(['pruebas' => 'Pruebas', 'produccion' => 'Producción'])
                            ->default('pruebas')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('pago_tarjeta')),
                        Forms\Components\TextInput::make('redsys_comercio')
                            ->label('Código de comercio (FUC)')
                            ->maxLength(9)
                            ->visible(fn (Get $get): bool => (bool) $get('pago_tarjeta')),
                        Forms\Components\TextInput::make('redsys_terminal')
                            ->label('Terminal')
                            ->default('1')
                            ->maxLength(3)
                            ->visible(fn (Get $get): bool => (bool) $get('pago_tarjeta')),
                        Forms\Components\TextInput::make('redsys_clave')
                            ->label('Clave secreta de firma (SHA-256)')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->helperText('Se guarda cifrada. Déjala vacía para conservar la que ya hay.')
                            ->visible(fn (Get $get): bool => (bool) $get('pago_tarjeta')),
                        Forms\Components\Placeholder::make('url_notificacion')
                            ->label('URL de notificación para el banco')
                            ->content(route('pago.notificacion'))
                            ->helperText('Algunos bancos piden darla de alta en el panel del TPV.')
                            ->visible(fn (Get $get): bool => (bool) $get('pago_tarjeta'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        // Una clave vacía significa "no la cambies", no "bórrala".
        if (blank($datos['redsys_clave'] ?? null)) {
            unset($datos['redsys_clave']);
        }

        Configuracion::actual()->update($datos);

        Notification::make()->title('Configuración de pagos guardada')->success()->send();

        $config = Configuracion::actual()->fresh();

        if ($config->pago_tarjeta && ! $config->cobraConTarjeta()) {
            Notification::make()
                ->title('Faltan datos del TPV')
                ->body('El pago con tarjeta no se ofrecerá hasta que estén el código de comercio, el terminal y la clave.')
                ->warning()
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
        ];
    }
}
