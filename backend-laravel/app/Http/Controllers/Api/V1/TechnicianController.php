<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkPart;
use App\Services\WorkPartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicianController extends Controller
{
    public function __construct(protected WorkPartService $workPartService) {}

    public function getOrders($tecnicoId)
    {
        \Log::info("Buscando ordenes para tecnico ID: {$tecnicoId}");

        $orders = WorkOrder::with(['customer', 'workParts' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where('assigned_tech_id', $tecnicoId)
            ->get();

        $mapped = $orders->map(function ($order) {
            return [
                'id'           => $order->id,
                'clientName'   => $order->customer->business_name ?? 'Sin cliente',
                'problem'      => $order->description ?? 'Sin descripcion',
                'address'      => $order->customer->address ?? 'Sin direccion',
                'priority'     => $this->mapPriority($order->priority),
                'status'       => $this->mapStatus($order->status),
                'created_at'   => $order->created_at->toISOString(),
                'completed_at' => $order->completed_at?->toISOString(),
                'scheduled_date' => $order->scheduled_date?->toISOString(),
                'scheduled_time' => $order->scheduled_time,
                'contact' => [
                    'name'  => $order->customer->contact_name ?? 'Sin contacto',
                    'phone' => $order->customer->phone ?? '',
                    'email' => $order->customer->email ?? '',
                ],
                'equipment' => $order->equipment ? [
                    'brand'  => $order->equipment->brand ?? 'Sin marca',
                    'model'  => $order->equipment->model ?? 'Sin modelo',
                    'serial' => $order->equipment->serial_number ?? 'Sin serial',
                ] : null,
                'notes' => $order->notes,
                'rejectedNote' => optional($order->workParts->sortByDesc('created_at')->first())->status === 'rejected'
                    ? optional($order->workParts->sortByDesc('created_at')->first())->supervisor_notes
                    : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $mapped]);
    }

    public function saveParte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orden_id'          => 'required|exists:work_orders,id',
            'tecnico_id'        => 'required|exists:users,id',
            'diagnostico'       => 'required|string',
            'trabajo_realizado' => 'required|string',
            'repuestos_usados'  => 'nullable|array',
            'firma_base64'      => 'required|string|min:100',
            'fotos'             => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $parte = $this->workPartService->submit($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Parte guardado exitosamente',
                'data'    => ['id' => $parte->id],
            ], 201);
        } catch (\Exception $e) {
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
                    'id'               => $parte->id,
                    'orden_id'         => $parte->work_order_id,
                    'cliente'          => $parte->workOrder->customer->business_name ?? 'Sin cliente',
                    'tecnico'          => $parte->technician->name ?? 'Sin tecnico',
                    'diagnostico'      => $parte->diagnosis,
                    'trabajo_realizado' => $parte->work_done,
                    'repuestos_usados' => $parte->parts_used,
                    'created_at'       => $parte->created_at->toISOString(),
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
            $data  = ['supervisor_notes' => $request->notes];

            if ($request->approved) {
                $this->workPartService->approve($parte, $data);
            } else {
                $this->workPartService->reject($parte, $data);
            }

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'Parte aprobado' : 'Parte rechazado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el parte',
                'error'   => $e->getMessage(),
            ], 500);
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
                'data'    => [
                    'id'               => $parte->id,
                    'diagnosis'        => $parte->diagnosis,
                    'work_done'        => $parte->work_done,
                    'signature'        => $parte->signature,
                    'status'           => $parte->status,
                    'supervisor_notes' => $parte->supervisor_notes,
                    'created_at'       => $parte->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener el parte', 'error' => $e->getMessage()], 500);
        }
    }

    private function mapPriority($priority): string
    {
        return ['low' => 'baja', 'medium' => 'media', 'high' => 'alta', 'urgent' => 'urgente'][$priority] ?? $priority;
    }

    private function mapStatus($status): string
    {
        return ['pending' => 'pendiente', 'in_progress' => 'en_progreso', 'completed' => 'completado'][$status] ?? 'pendiente';
    }
}