<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TechnicianController extends Controller
{
    public function getOrders($tecnicoId)
    {
        \Log::info("Buscando órdenes para técnico ID: {$tecnicoId}");

        $orders = WorkOrder::with(['customer', 'workParts' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_tech_id', $tecnicoId)
            ->get();

        \Log::info("Órdenes encontradas: " . $orders->count());

        $mapped = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'clientName' => $order->customer->business_name ?? 'Sin cliente',
                'problem' => $order->description ?? 'Sin descripción',
                'address' => $order->customer->address ?? 'Sin dirección',
                'priority' => $this->mapPriority($order->priority),
                'status' => $this->mapStatus($order->status),
                'created_at' => $order->created_at->toISOString(),
                'completed_at' => $order->completed_at?->toISOString(),
                'scheduled_date' => $order->scheduled_date?->toISOString(),
                'scheduled_time' => $order->scheduled_time,
                'contact' => [
                    'name' => $order->customer->contact_name ?? 'Sin contacto',
                    'phone' => $order->customer->phone ?? '',
                    'email' => $order->customer->email ?? '',
                ],
                'equipment' => $order->equipment ? [
                    'brand' => $order->equipment->brand ?? 'Sin marca',
                    'model' => $order->equipment->model ?? 'Sin modelo',
                    'serial' => $order->equipment->serial_number ?? 'Sin serial',
                ] : null,
                'notes' => $order->notes,
                'rejectedNote' => optional($order->workParts->sortByDesc('created_at')->first())->status === 'rejected'
                    ? optional($order->workParts->sortByDesc('created_at')->first())->supervisor_notes
                    : null,
            ];
        });

        \Log::info("Órdenes mapeadas: " . $mapped->count());

        return response()->json(['success' => true, 'data' => $mapped]);
    }

    public function saveParte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orden_id'         => 'required|exists:work_orders,id',
            'tecnico_id'       => 'required|exists:users,id',
            'diagnostico'      => 'required|string',
            'trabajo_realizado' => 'required|string',
            'repuestos_usados' => 'nullable|array',
            'firma_base64'     => 'required|string|min:100', // Mínimo 100 chars para asegurar que es una firma real
            'fotos'            => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $parte = WorkPart::create([
                'work_order_id' => $request->orden_id,
                'technician_id' => $request->tecnico_id,
                'diagnosis'     => $request->diagnostico,
                'work_done'     => $request->trabajo_realizado,
                'parts_used'    => $request->repuestos_usados,
                'signature'     => $request->firma_base64,
                'photos'        => $request->fotos,
                'status'        => 'pending_approval',
                'latitude'      => $request->lat,
                'longitude'     => $request->lng,
            ]);

            $order = WorkOrder::find($request->orden_id);
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            DB::commit();

            // Notificaciones en bloque separado — si fallan no afectan el guardado
            try {
                $technician = \App\Models\User::find($request->tecnico_id);
                // Buscar supervisores y super_admins usando Spatie roles
                $supervisors = \App\Models\User::role(['supervisor', 'super_admin'])->get();

                foreach ($supervisors as $supervisor) {
                    \Filament\Notifications\Notification::make()
                        ->title('Nuevo parte pendiente de aprobación')
                        ->body("Orden #{$order->id} - {$order->customer->business_name} completada por {$technician->name}")
                        ->icon('heroicon-o-clipboard-document-check')
                        ->iconColor('warning')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('ver')
                                ->label('Ver Parte')
                                ->url(\App\Filament\Resources\WorkPartResource::getUrl('view', ['record' => $parte->id]))
                                ->markAsRead(),
                        ])
                        ->sendToDatabase($supervisor);
                }
            } catch (\Exception $notifEx) {
                \Log::warning('Error enviando notificación de parte: ' . $notifEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Parte guardado exitosamente',
                'data'    => ['id' => $parte->id],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el parte',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getPendingPartes()
    {
        $partes = WorkPart::with(['workOrder.customer', 'technician'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($parte) {
                return [
                    'id'              => $parte->id,
                    'orden_id'        => $parte->work_order_id,
                    'cliente'         => $parte->workOrder->customer->business_name ?? 'Sin cliente',
                    'tecnico'         => $parte->technician->name ?? 'Sin técnico',
                    'diagnostico'     => $parte->diagnosis,
                    'trabajo_realizado' => $parte->work_done,
                    'repuestos_usados' => $parte->parts_used,
                    'created_at'      => $parte->created_at->toISOString(),
                ];
            });

        return response()->json(['success' => true, 'data' => $partes]);
    }

    public function approveParte(Request $request, $parteId)
    {
        $validator = Validator::make($request->all(), [
            'approved' => 'required|boolean',
            'notes'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $parte = WorkPart::findOrFail($parteId);
            $parte->status = $request->approved ? 'approved' : 'rejected';
            $parte->supervisor_notes = $request->notes;
            $parte->approved_at = $request->approved ? now() : null;
            $parte->save();

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'Parte aprobado' : 'Parte rechazado',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al procesar el parte', 'error' => $e->getMessage()], 500);
        }
    }

    public function getParte($workOrderId)
    {
        try {
            $parte = WorkPart::where('work_order_id', $workOrderId)->latest()->first();

            if (!$parte) {
                return response()->json(['success' => false, 'message' => 'Parte no encontrado'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id'         => $parte->id,
                    'diagnosis'  => $parte->diagnosis,
                    'work_done'  => $parte->work_done,
                    'signature'  => $parte->signature,
                    'status'            => $parte->status,
                    'supervisor_notes'  => $parte->supervisor_notes,
                    'created_at'        => $parte->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener el parte', 'error' => $e->getMessage()], 500);
        }
    }

    private function mapPriority($priority)
    {
        return ['low' => 'baja', 'medium' => 'media', 'high' => 'alta', 'urgent' => 'urgente'][$priority] ?? $priority;
    }

    private function mapStatus($status)
    {
        return ['pending' => 'pendiente', 'in_progress' => 'en_progreso', 'completed' => 'completado'][$status] ?? 'pendiente';
    }
}
