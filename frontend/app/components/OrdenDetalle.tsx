'use client';

import { useEffect, useState } from 'react';

interface OrdenDetalleProps {
  ordenId: number;
  onClose: () => void;
  apiBase: string;
}

export default function OrdenDetalle({ ordenId, onClose, apiBase }: OrdenDetalleProps) {
  const [orden, setOrden] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    cargarOrden();
  }, [ordenId]);

  const cargarOrden = async () => {
    try {
      const response = await fetch(`${apiBase}/ordenes/${ordenId}`);
      const result = await response.json();
      
      if (result.success) {
        setOrden(result.data);
      }
    } catch (error) {
      console.error('Error al cargar orden:', error);
    } finally {
      setLoading(false);
    }
  };

  const getEstadoColor = (estado: string) => {
    switch(estado) {
      case 'completado': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
      case 'en_progreso': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
      case 'pendiente': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
      case 'cancelado': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <div 
        className="fixed inset-0 bg-black bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        onClick={onClose}
      >
        <div className="bg-white rounded-lg p-8" onClick={(e) => e.stopPropagation()}>
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="text-center mt-4 text-gray-600">Cargando orden...</p>
        </div>
      </div>
    );
  }

  if (!orden) {
    return (
      <div 
        className="fixed inset-0 bg-black bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        onClick={onClose}
      >
        <div className="bg-white rounded-lg p-8 max-w-md" onClick={(e) => e.stopPropagation()}>
          <p className="text-center text-red-600">Error al cargar la orden</p>
          <button
            onClick={onClose}
            className="mt-4 w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Cerrar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div 
      className="fixed inset-0 bg-black bg-opacity-20 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto print-content"
      onClick={onClose}
    >
      <div 
        className="bg-white rounded-lg shadow-xl w-full max-w-4xl my-8 print:shadow-none print:my-0 print:max-w-full"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex justify-between items-start">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">Orden #{orden.numero_parte}</h2>
              <p className="text-sm text-gray-600 mt-1">
                {new Date(orden.fecha_trabajo).toLocaleDateString('es-AR', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                })}
              </p>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 text-2xl print:hidden"
            >
              ×
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
          
          {/* Estado y Acciones */}
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-200 print:hidden">
            <div>
              <span className={`px-3 py-1 rounded text-xs font-medium ${getEstadoColor(orden.estado)}`}>
                {orden.estado.toUpperCase()}
              </span>
            </div>
            <div className="flex gap-2">
              <button
                onClick={handlePrint}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
              >
                🖨️ Imprimir
              </button>
            </div>
          </div>

          {/* Info del Cliente */}
          <div className="bg-gray-50 rounded-lg p-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Información del Cliente</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="text-xs text-gray-500 font-medium">Nombre</label>
                <p className="text-gray-900 font-semibold">{orden.cliente_nombre}</p>
              </div>
              <div>
                <label className="text-xs text-gray-500 font-medium">Teléfono</label>
                <p className="text-gray-900">{orden.cliente_telefono || 'N/A'}</p>
              </div>
            </div>
          </div>

          {/* Info del Equipo */}
          <div className="bg-gray-50 rounded-lg p-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Información del Equipo</h3>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-xs text-gray-500 font-medium">Marca</label>
                <p className="text-gray-900 font-semibold">{orden.equipo_marca || 'N/A'}</p>
              </div>
              <div>
                <label className="text-xs text-gray-500 font-medium">Modelo</label>
                <p className="text-gray-900">{orden.equipo_modelo || 'N/A'}</p>
              </div>
              <div>
                <label className="text-xs text-gray-500 font-medium">Serie</label>
                <p className="text-gray-900">{orden.equipo_serie || 'N/A'}</p>
              </div>
            </div>
          </div>

          {/* Horarios */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="bg-gray-50 rounded-lg p-4">
              <label className="text-xs text-gray-500 font-medium">Hora Inicio</label>
              <p className="text-gray-900 font-semibold text-lg">{orden.hora_inicio || 'N/A'}</p>
            </div>
            <div className="bg-gray-50 rounded-lg p-4">
              <label className="text-xs text-gray-500 font-medium">Hora Fin</label>
              <p className="text-gray-900 font-semibold text-lg">{orden.hora_fin || 'N/A'}</p>
            </div>
          </div>

          {/* Trabajo Realizado */}
          <div className="bg-gray-50 rounded-lg p-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Descripción del Trabajo</h3>
            <p className="text-gray-700 whitespace-pre-wrap">{orden.descripcion_trabajo}</p>
            
            {orden.observaciones && (
              <div className="mt-4 pt-4 border-t border-gray-300">
                <label className="text-xs text-gray-500 font-medium block mb-1">Observaciones</label>
                <p className="text-gray-700 whitespace-pre-wrap">{orden.observaciones}</p>
              </div>
            )}
          </div>

          {/* Repuestos Utilizados */}
          {orden.repuestos && orden.repuestos.length > 0 && (
            <div className="bg-gray-50 rounded-lg p-4">
              <h3 className="text-lg font-semibold text-gray-900 mb-3">Repuestos Utilizados</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-200">
                    <tr>
                      <th className="px-3 py-2 text-left text-xs font-medium text-gray-700">Descripción</th>
                      <th className="px-3 py-2 text-center text-xs font-medium text-gray-700">Cantidad</th>
                      <th className="px-3 py-2 text-right text-xs font-medium text-gray-700">P. Unitario</th>
                      <th className="px-3 py-2 text-right text-xs font-medium text-gray-700">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-300">
                    {orden.repuestos.map((rep: any, idx: number) => (
                      <tr key={idx} className="hover:bg-gray-100">
                        <td className="px-3 py-2 text-gray-900">{rep.descripcion}</td>
                        <td className="px-3 py-2 text-center text-gray-900">{rep.cantidad}</td>
                        <td className="px-3 py-2 text-right text-gray-900">${Number(rep.precio_unitario).toLocaleString()}</td>
                        <td className="px-3 py-2 text-right font-medium text-gray-900">${(rep.cantidad * rep.precio_unitario).toLocaleString()}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Total */}
          <div className="bg-blue-50 rounded-lg p-6 border-2 border-blue-200">
            <div className="flex justify-between items-center">
              <span className="text-lg font-semibold text-gray-700">TOTAL</span>
              <span className="text-3xl font-bold text-blue-600">${Number(orden.total).toLocaleString('es-AR')}</span>
            </div>
          </div>

          {/* Técnico */}
          <div className="text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Técnico: <span className="font-semibold text-gray-700 dark:text-gray-300">{orden.tecnico_nombre || 'N/A'}</span></p>
          </div>
        </div>

        {/* Footer */}
        <div className="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg flex flex-col sm:flex-row justify-end gap-3 print:hidden">
          <button
            onClick={onClose}
            className="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition"
          >
            Cerrar
          </button>
          <button
            onClick={handlePrint}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            Imprimir Orden
          </button>
        </div>
      </div>
    </div>
  );
}
