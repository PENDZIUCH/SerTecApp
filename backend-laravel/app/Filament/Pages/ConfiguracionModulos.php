<?php

namespace App\Filament\Pages;

use App\Services\ModuleManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionModulos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Módulos';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 82;
    protected static string $view = 'filament.pages.configuracion-modulos';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $modules = ModuleManager::all();
        $this->form->fill([
            'mod_customers'    => (bool) ($modules['customers'] ?? true),
            'mod_work_orders'  => (bool) ($modules['work_orders'] ?? true),
            'mod_visits'       => (bool) ($modules['visits'] ?? true),
            'mod_budgets'      => (bool) ($modules['budgets'] ?? true),
            'mod_parts'        => (bool) ($modules['parts'] ?? true),
            'mod_workshop'     => (bool) ($modules['workshop'] ?? true),
            'mod_subscriptions' => (bool) ($modules['subscriptions'] ?? true),
            'mod_equipment'    => (bool) ($modules['equipment'] ?? true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Core — siempre activo')
                    ->description('Módulos base que aplican a cualquier cliente.')
                    ->schema([
                        Forms\Components\Toggle::make('mod_customers')
                            ->label('Clientes')
                            ->helperText('Gestión de clientes, contactos, direcciones y notas.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_work_orders')
                            ->label('Órdenes de trabajo + Partes')
                            ->helperText('Flujo completo: crear orden, asignar técnico, aprobar/rechazar parte, firma del cliente.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_visits')
                            ->label('Visitas / Agenda')
                            ->helperText('Programación de visitas a clientes.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_budgets')
                            ->label('Presupuestos')
                            ->helperText('Generación y seguimiento de presupuestos.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_subscriptions')
                            ->label('Suscripciones')
                            ->helperText('Facturación recurrente y renovaciones.')
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Operaciones')
                    ->description('Módulos para empresas con inventario físico o taller.')
                    ->schema([
                        Forms\Components\Toggle::make('mod_parts')
                            ->label('Stock / Repuestos')
                            ->helperText('Inventario de repuestos y movimientos de stock.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_workshop')
                            ->label('Taller')
                            ->helperText('Gestión de ítems en taller.')
                            ->inline(false),
                        Forms\Components\Toggle::make('mod_equipment')
                            ->label('Equipamiento')
                            ->helperText('Activos físicos del cliente: marcas, modelos, historial de servicio.')
                            ->inline(false),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $map = [
            'mod_customers'    => 'customers',
            'mod_work_orders'  => 'work_orders',
            'mod_visits'       => 'visits',
            'mod_budgets'      => 'budgets',
            'mod_parts'        => 'parts',
            'mod_workshop'     => 'workshop',
            'mod_subscriptions' => 'subscriptions',
            'mod_equipment'    => 'equipment',
        ];

        foreach ($map as $field => $module) {
            if ($data[$field] ?? false) {
                ModuleManager::activate($module);
            } else {
                ModuleManager::deactivate($module);
            }
        }

        Notification::make()
            ->title('Módulos actualizados')
            ->body('Los cambios se aplican al recargar el panel.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar cambios')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
