<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\BookingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

/**
 * Pensado para el caso de uso real: el supervisor arma el dia completo de
 * un tecnico (varias ordenes de trabajo, distintos horarios) en una sola
 * pantalla, en vez de abrir "Nueva Visita" una vez por cada parada
 * repitiendo tecnico y fecha cada vez.
 */
class ArmarRecorrido extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.resources.booking-resource.pages.armar-recorrido';

    protected static ?string $title = 'Armar Recorrido';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'resource_id' => null,
            'dia' => today()->toDateString(),
            'paradas' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('resource_id')
                    ->label('Técnico')
                    ->options(fn () => User::query()->role(['técnico', 'tecnico'])->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('dia')
                    ->label('Día del recorrido')
                    ->required(),

                Forms\Components\Repeater::make('paradas')
                    ->label('Paradas')
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label('Orden de Trabajo')
                            ->options(fn () => WorkOrder::query()->pluck('wo_number', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TimePicker::make('hora')
                            ->label('Hora')
                            ->required(),
                        Forms\Components\TextInput::make('duracion_minutos')
                            ->label('Duración estimada (min)')
                            ->numeric()
                            ->default(60)
                            ->required(),
                    ])
                    ->columns(3)
                    ->addActionLabel('+ Agregar parada')
                    ->reorderable()
                    ->minItems(1)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(BookingService $bookingService): void
    {
        $state = $this->form->getState();

        $dia = $state['dia'];
        $creadas = 0;

        foreach ($state['paradas'] as $parada) {
            $startsAt = \Illuminate\Support\Carbon::parse($dia . ' ' . $parada['hora']);

            $bookingService->create([
                'resource_type' => User::class,
                'resource_id' => $state['resource_id'],
                'subject_type' => WorkOrder::class,
                'subject_id' => $parada['subject_id'],
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->clone()->addMinutes((int) ($parada['duracion_minutos'] ?? 60)),
                'status' => 'scheduled',
            ]);

            $creadas++;
        }

        Notification::make()
            ->title($creadas === 1 ? '1 parada agendada' : "{$creadas} paradas agendadas")
            ->success()
            ->send();

        $this->redirect(BookingResource::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar recorrido')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}
