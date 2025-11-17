'use client';

import { useState, useEffect } from 'react';

interface OrdenFormProps {
  onClose: () => void;
  onSuccess: (message: string) => void;
  orden?: any;
  apiBase: string;
}

export default function OrdenForm({ onClose, onSuccess, orden, apiBase }: OrdenFormProps) {
  const isEdit = !!orden;
  const [clientes, setClientes] = useState([]);
  const [repuestos, setRepuestos] = useState([]);
  const [selectedRepuestos, setSelectedRepuestos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [clientesRes, repuestosRes] = await Promise.all([
        fetch(`${apiBase}/clientes`),
        fetch(`${apiBase}/repuestos`)
      ]);
      
      const clientesData = await clientesRes.json();
      const repuestosData = await repuestosRes.json();
      
      setClientes(clientesData.data?.data || []);
      setRepuestos(repuestosData.data?.data || []);
      
      if (orden?.repuestos) {
        setSelectedRepuestos(orden.repuestos);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    } finally {
      setLoading(false);
    }
  };

  const agregarRepuesto = () => {
    setSelectedRepuestos([...selectedRepuestos, {
      repuesto_id: '',
      cantidad: 1,
      precio_unitario: 0
    }]);
  };

  const eliminarRepuesto = (index: number) => {
    setSelectedRepuestos(selectedRepuestos.filter((_, i) => i !== index));
  };

  const actualizarRepuesto = (index: number, field: string, value: any) => {
    const nuevosRepuestos = [...selectedRepuestos];
    nuevosRepuestos[index] = { ...nuevosRepuestos[index], [field]: value };
    
    // Si cambió el repuesto, actualizar el precio
    if (field === 'repuesto_id') {
      const repuesto = repuestos.find((r: any) => r.id === parseInt(value));
      if (repuesto) {
        nuevosRepuestos[index].precio_unitario = repuesto.precio_unitario;
      }
    }
    
    setSelectedRepuestos(nuevosRepuestos);
  };

  const calcularTotal = () => {
    return selectedRepuestos.reduce((sum, item) => {
      return sum + (item.cantidad * item.precio_unitario);
    }, 0);
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    
    const clienteId = formData.get('cliente_id');
    if (!clienteId || clienteId === '') {
      alert('Por favor selecciona un cliente');
      return;
    }
    
    const data = {
      numero_parte: formData.get('numero_parte'),
      cliente_id: parseInt(clienteId as string),
      tecnico_id: 1,
      fecha_trabajo: formData.get('fecha_trabajo'),
      hora_inicio: formData.get('hora_inicio') || null,
      hora_fin: formData.get('hora_fin') || null,
      equipo_marca: formData.get('equipo_marca') || null,
      equipo_modelo: formData.get('equipo_modelo') || null,
      equipo_serie: formData.get('equipo_serie') || null,
      descripcion_trabajo: formData.get('descripcion_trabajo'),
      observaciones: formData.get('observaciones') || null,
      estado: formData.get('estado') || 'pendiente',
      repuestos: selectedRepuestos.filter(r => r.repuesto_id && r.repuesto_id !== '').map(r => ({
        repuesto_id: parseInt(r.repuesto_id),
        cantidad: parseInt(r.cantidad) || 1,
        precio_unitario: parseFloat(r.precio_unitario) || 0
      }))
    };

    console.log('Datos a enviar:', JSON.stringify(data, null, 2));

    try {
      const url = isEdit 
        ? `${apiBase}/ordenes/${orden.id}` 
        : `${apiBase}/ordenes`;
      
      console.log('URL:', url);
      
      const response = await fetch(url, {
        method: isEdit ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      console.log('Response status:', response.status);
      
      const responseText = await response.text();
      console.log('Response text:', responseText);
      
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (e) {
        console.error('Error parsing JSON:', e);
        alert('Error: Respuesta inválida del servidor\n\n' + responseText);
        return;
      }
      
      console.log('Resultado parseado:', result);
      
      if (result.success) {
        onClose();
        onSuccess(isEdit ? 'Orden actualizada correctamente' : 'Orden creada correctamente');
      } else {
        alert('Error: ' + (result.message || 'No se pudo guardar la orden') + 
              (result.error ? '\n\nDetalle: ' + result.error : ''));
      }
    } catch (error) {
      console.error('Error completo:', error);
      alert('Error de conexión: ' + error);
    }
  };

  if (loading) {
    return (
      <div 
        className="fixed inset-0 bg-black bg-opacity-20 backdrop-blur-sm flex items-center justify-center p-4 z-50"
        onClick={onClose}
      >
        <div className="bg-white rounded-lg p-8" onClick={(e) => e.stopPropagation()}>
          <div className="text-center text-gray-900">Cargando...</div>
        </div>
      </div>
    );
  }

  return (
    <div 
      className="fixed inset-0 bg-black bg-opacity-20 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
      onClick={onClose}
    >
      <div 
        className="bg-white rounded-lg shadow-xl max-w-4xl w-full my-8"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold text-gray-900">
              {isEdit ? 'Editar Orden de Trabajo' : 'Nueva Orden de Trabajo'}
            </h2>
            <button 
              onClick={onClose} 
              className="text-gray-400 hover:text-gray-600 text-2xl"
            >
              ×
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Información básica */}
            <div className="border-b pb-4">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Información General</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Número Parte *
                  </label>
                  <input
                    type="text"
                    name="numero_parte"
                    defaultValue={orden?.numero_parte || `OT-${Date.now()}`}
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Cliente *
                  </label>
                  <select
                    name="cliente_id"
                    defaultValue={orden?.cliente_id}
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  >
                    <option value="">Seleccionar cliente</option>
                    {clientes.map((c: any) => (
                      <option key={c.id} value={c.id}>{c.nombre}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Estado
                  </label>
                  <select
                    name="estado"
                    defaultValue={orden?.estado || 'pendiente'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  >
                    <option value="pendiente">Pendiente</option>
                    <option value="en_progreso">En Progreso</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Trabajo *
                  </label>
                  <input
                    type="date"
                    name="fecha_trabajo"
                    defaultValue={orden?.fecha_trabajo || new Date().toISOString().split('T')[0]}
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Hora Inicio
                  </label>
                  <input
                    type="time"
                    name="hora_inicio"
                    defaultValue={orden?.hora_inicio || '09:00'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Hora Fin
                  </label>
                  <input
                    type="time"
                    name="hora_fin"
                    defaultValue={orden?.hora_fin}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>
              </div>
            </div>

            {/* Información del equipo */}
            <div className="border-b pb-4">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Equipo</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Marca
                  </label>
                  <input
                    type="text"
                    name="equipo_marca"
                    defaultValue={orden?.equipo_marca}
                    placeholder="Body Fitness"
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Modelo
                  </label>
                  <input
                    type="text"
                    name="equipo_modelo"
                    defaultValue={orden?.equipo_modelo}
                    placeholder="PT300"
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nº Serie
                  </label>
                  <input
                    type="text"
                    name="equipo_serie"
                    defaultValue={orden?.equipo_serie}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>
              </div>
            </div>

            {/* Descripción del trabajo */}
            <div className="border-b pb-4">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Trabajo Realizado</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Descripción del Trabajo *
                  </label>
                  <textarea
                    name="descripcion_trabajo"
                    defaultValue={orden?.descripcion_trabajo}
                    required
                    rows={3}
                    placeholder="Cambio de banda en cinta, ajuste de tensión..."
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Observaciones
                  </label>
                  <textarea
                    name="observaciones"
                    defaultValue={orden?.observaciones}
                    rows={2}
                    placeholder="Notas adicionales..."
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                  />
                </div>
              </div>
            </div>

            {/* Repuestos */}
            <div>
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold text-gray-900">Repuestos Utilizados</h3>
                <button
                  type="button"
                  onClick={agregarRepuesto}
                  className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm"
                >
                  + Agregar Repuesto
                </button>
              </div>

              {selectedRepuestos.length === 0 ? (
                <p className="text-gray-500 text-center py-4">No hay repuestos agregados</p>
              ) : (
                <div className="space-y-3">
                  {selectedRepuestos.map((item, index) => (
                    <div key={index} className="flex gap-3 items-end bg-gray-50 p-3 rounded-lg">
                      <div className="flex-1">
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                          Repuesto
                        </label>
                        <select
                          value={item.repuesto_id}
                          onChange={(e) => actualizarRepuesto(index, 'repuesto_id', e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-900 text-sm"
                        >
                          <option value="">Seleccionar</option>
                          {repuestos.map((r: any) => (
                            <option key={r.id} value={r.id}>
                              {r.descripcion} - ${r.precio_unitario.toLocaleString()}
                            </option>
                          ))}
                        </select>
                      </div>

                      <div className="w-24">
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                          Cantidad
                        </label>
                        <input
                          type="number"
                          min="1"
                          value={item.cantidad}
                          onChange={(e) => actualizarRepuesto(index, 'cantidad', parseInt(e.target.value))}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-900 text-sm"
                        />
                      </div>

                      <div className="w-32">
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                          Precio Unit.
                        </label>
                        <input
                          type="number"
                          value={item.precio_unitario}
                          onChange={(e) => actualizarRepuesto(index, 'precio_unitario', parseFloat(e.target.value))}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-900 text-sm"
                        />
                      </div>

                      <div className="w-32">
                        <label className="block text-xs font-medium text-gray-700 mb-1">
                          Subtotal
                        </label>
                        <div className="px-3 py-2 bg-gray-100 rounded-lg text-gray-900 text-sm font-medium">
                          ${(item.cantidad * item.precio_unitario).toLocaleString()}
                        </div>
                      </div>

                      <button
                        type="button"
                        onClick={() => eliminarRepuesto(index)}
                        className="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm"
                      >
                        ×
                      </button>
                    </div>
                  ))}

                  <div className="flex justify-end pt-3 border-t">
                    <div className="text-right">
                      <div className="text-sm text-gray-600">Total</div>
                      <div className="text-2xl font-bold text-gray-900">
                        ${calcularTotal().toLocaleString()}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="flex justify-end gap-3 pt-6 border-t">
              <button
                type="button"
                onClick={onClose}
                className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                {isEdit ? 'Actualizar' : 'Crear'} Orden
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
