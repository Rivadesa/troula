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
class PaginaPrivacidad extends Page implements HasForms
{
    use InteractsWithForms;
    use SoloAdministradores;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Privacidad';

    protected static ?string $title = 'Política de privacidad';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.pagina-privacidad';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $cfg = Configuracion::actual();

        $this->form->fill([
            'politica_privacidad' => $cfg->politica_privacidad ?: $this->plantillaPorDefecto($cfg),
            'politica_privacidad_url' => $cfg->politica_privacidad_url,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Texto de la política de privacidad')
                    ->description('Se publica en la página pública /privacidad, a la que enlaza el checkbox de LOPD del configurador.')
                    ->schema([
                        Forms\Components\RichEditor::make('politica_privacidad')
                            ->hiddenLabel()
                            ->toolbarButtons(['bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Avanzado')
                    ->schema([
                        Forms\Components\TextInput::make('politica_privacidad_url')
                            ->label('URL externa (opcional)')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Si la rellenas, el checkbox de LOPD enlazará a esta URL en lugar de a la página /privacidad.'),
                    ])->collapsed(),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        Configuracion::actual()->update($this->form->getState());

        Notification::make()->title('Política de privacidad guardada')->success()->send();
    }

    private function plantillaPorDefecto(Configuracion $cfg): string
    {
        $responsable = e($cfg->nombre);
        $cif = $cfg->cif ? ' ('.e($cfg->cif).')' : '';
        $direccion = collect([$cfg->direccion, $cfg->codigo_postal, $cfg->ciudad])->filter()->map(fn ($v) => e($v))->join(', ');
        $email = e($cfg->email ?: 'tu-email@dominio.com');

        return <<<HTML
            <h2>Política de privacidad</h2>
            <p><em>Texto orientativo: revísalo y adáptalo a tu actividad antes de publicarlo.</em></p>
            <p><strong>Responsable del tratamiento:</strong> {$responsable}{$cif}. {$direccion}. Email: {$email}.</p>
            <p><strong>Finalidad:</strong> gestionar las solicitudes de reserva realizadas a través del configurador y mantener la relación con el cliente.</p>
            <p><strong>Legitimación:</strong> el consentimiento del interesado, otorgado al marcar la casilla correspondiente.</p>
            <p><strong>Conservación:</strong> los datos se conservarán mientras dure la relación y durante los plazos legalmente exigidos.</p>
            <p><strong>Destinatarios:</strong> no se cederán datos a terceros salvo obligación legal.</p>
            <p><strong>Derechos:</strong> puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a {$email}.</p>
            HTML;
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')->label('Guardar')->submit('guardar'),
            Action::make('ver')
                ->label('Ver página pública')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => url('/privacidad'), shouldOpenInNewTab: true),
        ];
    }
}
