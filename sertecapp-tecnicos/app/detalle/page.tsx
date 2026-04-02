'use client';

import { useRouter, useSearchParams } from 'next/navigation';
import { useEffect, useState, Suspense } from 'react';

function DetalleContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const orderId = searchParams.get('id') || '';
  const [order, setOrder] = useState<any>(null);
  const [parte, setParte] = useState<any>(null);

  useEffect(() => {
    if (!orderId) { router.push('/ordenes'); return; }
    const cachedOrders = localStorage.getItem('sertecapp_ordenes_cache');
    if (cachedOrders) {
      try {
        const parsed = JSON.parse(cachedOrders);
        const orders = Array.isArray(parsed) ? parsed : (parsed.data || []);
        const foundOrder = orders.find((o: any) => o.id.toString() === orderId);
        if (foundOrder) {
          setOrder(foundOrder);
          if (foundOrder.status === 'completado') loadParte(foundOrder.id);
          return;
        }
      } catch (e) { console.error(e); }
    }
    router.push('/ordenes');
  }, [orderId]);

  const loadParte = async (id: number) => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`https://sertecapp-worker.pendziuch.workers.dev/api/v1/partes/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (data.success && data.data) setParte(data.data);
    } catch (e) { console.error(e); }
  };

  if (!order) return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
    </div>
  );

  const priorityColors: any = { urgente: 'bg-red-500', alta: 'bg-orange-500', media: 'bg-yellow-500', baja: 'bg-gray-500' };
  const statusLabels: any = { pendiente: 'Pendiente', en_progreso: 'En Proceso', completado: 'Completado' };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3">
          <button onClick={() => router.back()} className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg className="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 className="text-base font-semibold text-gray-900">Detalle de Orden</h1>
            <p className="text-sm text-gray-500">#{order.id.toString().padStart(4, '0')}</p>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div className="bg-white rounded-lg p-4 border border-gray-200">
            <h2 className="text-sm font-semibold text-gray-900 mb-3">Cliente</h2>
            <p className="text-lg font-semibold text-gray-900">{order.clientName}</p>
            {order.address && (
              <div className="flex items-start gap-2 text-sm text-gray-600 mt-2">
                <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{order.address}</span>
              </div>
            )}
          </div>

          <div className="bg-white rounded-lg p-4 border border-gray-200">
            <h2 className="text-sm font-semibold text-gray-900 mb-3">Problema Reportado</h2>
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <span className={`${priorityColors[order.priority] || 'bg-gray-500'} text-white text-xs font-medium px-2.5 py-0.5 rounded-full`}>
                  {(order.priority || 'normal').toUpperCase()}
                </span>
                <span className="text-xs text-gray-600">{statusLabels[order.status] || order.status}</span>
              </div>
              <p className="text-sm text-gray-900">{order.problem || order.title}</p>
            </div>
          </div>

          {order.equipment && (
            <div className="bg-white rounded-lg p-4 border border-gray-200">
              <h2 className="text-sm font-semibold text-gray-900 mb-3">Equipo</h2>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Marca:</span>
                  <span className="font-medium text-gray-900">{order.equipment.brand || 'N/A'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Modelo:</span>
                  <span className="font-medium text-gray-900">{order.equipment.model || 'N/A'}</span>
                </div>
              </div>
            </div>
          )}
        </div>

        {order.status === 'completado' && parte && (
          <div className="bg-green-50 rounded-lg p-4 border border-green-200 mb-4">
            <h2 className="text-sm font-semibold text-green-900 mb-3">Trabajo Completado</h2>
            <div className="space-y-3">
              <div>
                <p className="text-xs font-medium text-gray-700 mb-1">Diagnóstico:</p>
                <p className="text-sm text-gray-900">{parte.diagnosis || parte.diagnostico}</p>
              </div>
              <div>
                <p className="text-xs font-medium text-gray-700 mb-1">Trabajo Realizado:</p>
                <p className="text-sm text-gray-900">{parte.work_done || parte.trabajo_realizado}</p>
              </div>
              {parte.signature && (
                <div>
                  <p className="text-xs font-medium text-gray-700 mb-2">Firma:</p>
                  <img src={parte.signature} alt="Firma" className="border border-gray-300 rounded-lg max-w-xs" />
                </div>
              )}
            </div>
          </div>
        )}

        {order.status === 'pendiente' && (
          <div className="sticky bottom-4 mt-4">
            <button onClick={() => router.push(`/parte?id=${order.id}`)}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-lg shadow-lg transition-colors">
              Completar Parte de Trabajo
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

export default function DetallePage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>}>
      <DetalleContent />
    </Suspense>
  );
}
