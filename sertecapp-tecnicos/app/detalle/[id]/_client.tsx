'use client';

import { useRouter, useParams } from 'next/navigation';
import { useEffect, useState } from 'react';

export default function DetallePage() {
  const router = useRouter();
  const params = useParams();
  const [order, setOrder] = useState<any>(null);
  const [parte, setParte] = useState<any>(null);

  useEffect(() => {
    const orderId = params.id as string;
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
      } catch (e) {
        console.error('Error parsing cache:', e);
      }
    }
    router.push('/ordenes');
  }, [params.id, router]);

  const loadParte = async (orderId: number) => {
    try {
      const response = await fetch(`https://sertecapp.pendziuch.com/api/v1/partes/${orderId}`);
      const data = await response.json();
      if (data.success && data.data) setParte(data.data);
    } catch (error) {
      console.error('Error loading parte:', error);
    }
  };

  if (!order) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>
    );
  }

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
            <div className="flex items-start gap-2 text-sm text-gray-600 mt-2">
              <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{order.address}</span>
            </div>
          </div>

          {order.contact && (
            <div className="bg-white rounded-lg p-4 border border-gray-200">
              <h2 className="text-sm font-semibold text-gray-900 mb-3">Contacto</h2>
              <div className="space-y-2">
                <p className="text-sm text-gray-900">{order.contact.name}</p>
                {order.contact.phone && (
                  <a href={`tel:${order.contact.phone}`} className="flex items-center gap-2 text-sm text-blue-600">
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    {order.contact.phone}
                  </a>
                )}
                {order.contact.email && (
                  <a href={`mailto:${order.contact.email}`} className="flex items-center gap-2 text-sm text-blue-600">
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {order.contact.email}
                  </a>
                )}
              </div>
            </div>
          )}

          <div className="bg-white rounded-lg p-4 border border-gray-200">
            <h2 className="text-sm font-semibold text-gray-900 mb-3">Problema Reportado</h2>
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <span className={`${priorityColors[order.priority]} text-white text-xs font-medium px-2.5 py-0.5 rounded-full`}>
                  {order.priority.toUpperCase()}
                </span>
                <span className="text-xs text-gray-600">{statusLabels[order.status]}</span>
              </div>
              <p className="text-sm text-gray-900">{order.problem}</p>
              {order.notes && (
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-xs font-medium text-gray-700 mb-1">Notas:</p>
                  <p className="text-sm text-gray-600">{order.notes}</p>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="space-y-4">
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
                <div className="flex justify-between">
                  <span className="text-gray-600">Serial:</span>
                  <span className="font-mono text-xs text-gray-900">{order.equipment.serial || 'N/A'}</span>
                </div>
              </div>
            </div>
          )}

          {order.status === 'completado' && parte && (
            <div className="bg-green-50 rounded-lg p-4 border border-green-200">
              <h2 className="text-sm font-semibold text-green-900 mb-3 flex items-center gap-2">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Trabajo Completado
              </h2>
              <div className="space-y-3">
                <div>
                  <p className="text-xs font-medium text-gray-700 mb-1">Diagnóstico:</p>
                  <p className="text-sm text-gray-900">{parte.diagnosis}</p>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-700 mb-1">Trabajo Realizado:</p>
                  <p className="text-sm text-gray-900">{parte.work_done}</p>
                </div>
                {parte.signature && (
                  <div>
                    <p className="text-xs font-medium text-gray-700 mb-2">Firma del Cliente:</p>
                    <img src={parte.signature} alt="Firma" className="border border-gray-300 rounded-lg max-w-full h-auto" />
                  </div>
                )}
                <div className="flex items-center gap-2 text-xs text-gray-600 pt-2 border-t border-green-200">
                  <span className="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full font-medium">Pendiente de Aprobación</span>
                </div>
              </div>
            </div>
          )}
        </div>

        {order.status === 'pendiente' && (
          <div className="sticky bottom-4 mt-4">
            <button onClick={() => router.push(`/parte/${order.id}`)}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-lg shadow-lg transition-colors">
              Completar Parte de Trabajo
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
