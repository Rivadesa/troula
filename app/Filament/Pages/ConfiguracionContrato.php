<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloAdministradores;
use App\Models\Configuracion;
use App\Services\ContratoService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * Plantilla del contrato de prestación de servicios.
 *
 * @property-read Form $form
 */
class ConfiguracionContrato extends Page implements HasForms
{
    use InteractsWithForms;
    use SoloAdministradores;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Contrato';

    protected static ?string $title = 'Contrato de prestación de servicios';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.configuracion-contrato';

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
                Forms\Components\Section::make('Plantilla')
                    ->description('Es el texto que el cliente lee y acepta antes de pagar la señal. Si lo dejas vacío, no se le pedirá firmar nada.')
                    ->schema([
                        Forms\Components\Textarea::make('contrato_plantilla')
                            ->hiddenLabel()
                            ->rows(28)
                            ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 12px;'])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Huecos que puedes usar')
                    ->description('Escríbelos tal cual, entre llaves dobles. Se sustituyen por los datos de cada reserva al generar el contrato.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('campos')
                            ->hiddenLabel()
                            ->content(new HtmlString($this->tablaDeCampos()))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        Configuracion::actual()->update($this->form->getState());

        Notification::make()->title('Plantilla del contrato guardada')->success()->send();

        $this->avisarDeHuecosDesconocidos();
    }

    /**
     * Avisa si la plantilla usa huecos que no existen: se sustituirían por vacío
     * y el cliente vería un contrato con lagunas.
     */
    private function avisarDeHuecosDesconocidos(): void
    {
        $plantilla = (string) Configuracion::actual()->fresh()->contrato_plantilla;

        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $plantilla, $encontrados);

        $desconocidos = array_diff(
            array_unique(array_map(strtolower(...), $encontrados[1])),
            array_keys(ContratoService::campos()),
        );

        if ($desconocidos !== []) {
            Notification::make()
                ->title('Hay huecos que no reconozco')
                ->body('Se quedarán vacíos en el contrato: '.implode(', ', $desconocidos))
                ->warning()
                ->persistent()
                ->send();
        }
    }

    private function tablaDeCampos(): string
    {
        $filas = '';

        foreach (ContratoService::campos() as $clave => $descripcion) {
            $filas .= '<tr>'
                .'<td style="padding:.25rem .75rem .25rem 0;font-family:ui-monospace,monospace;white-space:nowrap">{{'.$clave.'}}</td>'
                .'<td style="padding:.25rem 0;opacity:.75">'.e($descripcion).'</td>'
                .'</tr>';
        }

        return '<table style="font-size:.8rem">'.$filas.'</table>';
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
