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
    /**
     * GET /api/v1/ordenes/tecnico/{tecnico}
     * Obtener órdenes de trabajo asignadas a un técnico
     */
    public function getOrders($tecnicoId)
    {
        \Log::info("Buscando órdenes para técnico ID: {$tecnicoId}");
        
        $orders = WorkOrder::with(['customer'])
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
                ];
            });
        
        \Log::info("Órdenes mapeadas: " . $mapped->count());
        
        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }

    /**
     * POST /api/v1/partes
     * Guardar un parte de trabajo completado
     */
    public function saveParte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orden_id' => 'required|exists:work_orders,id',
            'tecnico_id' => 'required|exists:users,id',
            'diagnostico' => 'required|string',
            'trabajo_realizado' => 'required|string',
            'repuestos_usados' => 'nullable|array',
            'firma_base64' => 'nullable|string',
            'fotos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $parte = WorkPart::create([
                'work_order_id' => $request->orden_id,
                'technician_id' => $request->tecnico_id,
                'diagnosis' => $request->diagnostico,
                'work_done' => $request->trabajo_realizado,
                'parts_used' => $request->repuestos_usados,
                'signature' => $request->firma_base64,
                'photos' => $request->fotos,
                'status' => 'pending_approval',
            ]);

            // Actualizar estado de la orden
            $order = WorkOrder::find($request->orden_id);
            $order->status = 'completed';
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parte guardado exitosamente',
                'data' => [
                    'id' => $parte->id,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el parte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/partes/pendientes
     * Obtener partes pendientes de aprobación (para supervisor)
     */
    public function getPendingPartes()
    {
        $partes = WorkPart::with(['workOrder.customer', 'technician'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($parte) {
                return [
                    'id' => $parte->id,
                    'orden_id' => $parte->work_order_id,
                    'cliente' => $parte->workOrder->customer->business_name ?? 'Sin cliente',
                    'tecnico' => $parte->technician->name ?? 'Sin técnico',
                    'diagnostico' => $parte->diagnosis,
                    'trabajo_realizado' => $parte->work_done,
                    'repuestos_usados' => $parte->parts_used,
                    'created_at' => $parte->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $partes,
        ]);
    }

    /**
     * PUT /api/v1/partes/{parte}/aprobar
     * Aprobar o rechazar un parte de trabajo
     */
    public function approveParte(Request $request, $parteId)
    {
        $validator = Validator::make($request->all(), [
            'approved' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
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
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el parte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Mapear prioridad
     */
    private function mapPriority($priority)
    {
        $map = [
            1 => 'baja',
            2 => 'media',
            3 => 'alta',
            4 => 'urgente',
        ];

        return $map[$priority] ?? 'media';
    }

    /**
     * Helper: Mapear estado
     */
    private function mapStatus($status)
    {
        $map = [
            'pending' => 'pendiente',
            'in_progress' => 'en_progreso',
            'completed' => 'completado',
        ];

        return $map[$status] ?? 'pendiente';
    }
}
