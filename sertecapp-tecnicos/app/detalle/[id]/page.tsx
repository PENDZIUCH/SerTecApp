'use client';

import { useRouter, useParams } from 'next/navigation';
import { useEffect, useState } from 'react';

// TODO: Traer del backend cuando esté listo
const DEMO_ORDERS: any = {
  1: {
    id: 1,
    clientName: 'Gym Centro',
    problem: 'Cinta no enciende',
    address: 'Av. Libertador 1234, CABA',
    priority: 'urgente',
    status: 'pendiente',
    created_at: '2025-12-30T10:00:00',
    contact: {
      name: 'Carlos Pérez',
      phone: '+54 11 1234-5678',
      email: 'carlos@gymcentro.com'
    },
    equipment: {
      brand: 'Body Fitness',
      model: 'PT300',
      serial: 'BF-PT300-2023-001'
    },
    notes: 'Cliente reporta que la cinta no enciende desde ayer. Revisaron el enchufe y funciona.',
  },
  2: {
    id: 2,
    clientName: 'Club Fitness Sur',
    problem: 'Bici hace ruido en pedal derecho',
    address: 'Mitre 567, Avellaneda',
    priority: 'media',
    status: 'pendiente',
    created_at: '2025-12-30T11:30:00',
    contact: {
      name: 'Ana García',
      phone: '+54 11 8765-4321',
      email: 'ana@fitnesssur.com'
    },
    equipment: {
      brand: 'Schwinn',
      model: 'IC2',
      serial: 'SW-IC2-2022-045'
    },
    notes: 'Ruido metálico en el pedal derecho al pedalear.',
  },
  3: {
    id: 3,
    clientName: 'Fitness Company',
    problem: 'Remo pierde resistencia',
    address: 'San Martín 890, San Isidro',
    priority: 'alta',
    status: 'pendiente',
    created_at: '2025-12-30T09:15:00',
    contact: {
      name: 'Luis Martínez',
      phone: '+54 11 5555-6666',
      email: 'luis@fitnesscompany.com'
    },
    equipment: {
      brand: 'Life Fitness',
      model: 'GX',
      serial: 'LF-GX-2021-089'
    },
    notes: 'La resistencia del remo baja progresivamente durante el uso.',
  },
};

export default function DetallePage() {
  const router = useRouter();
  const params = useParams();
  const [order, setOrder] = useState<any>(null);

  useEffect(() => {
    const orderId = params.id as string;
    const orderData = DEMO_ORDERS[parseInt(orderId)];
    
    if (orderData) {
      setOrder(orderData);
    } else {
      router.push('/ordenes');
    }
  }, [params, router]);

  if (!order) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  const priorityColors: any = {
    urgente: 'bg-red-500',
    alta: 'bg-orange-500',
    media: 'bg-yellow-500',
    baja: 'bg-gray-500',
  };

  const statusLabels: any = {
    pendiente: 'Pendiente',
    en_progreso: 'En Proceso',
    completado: 'Completado',
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3">
          <button
            onClick={() => router.back()}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
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

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 py-6 space-y-4">
        {/* Cliente */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <h2 className="text-sm font-semibold text-gray-900 mb-3">Cliente</h2>
          <div className="space-y-2">
            <div>
              <p className="text-lg font-semibold text-gray-900">{order.clientName}</p>
            </div>
            <div className="flex items-start gap-2 text-sm text-gray-600">
              <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{order.address}</span>
            </div>
          </div>
        </div>

        {/* Contacto */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <h2 className="text-sm font-semibold text-gray-900 mb-3">Contacto</h2>
          <div className="space-y-2">
            <p className="text-sm text-gray-900">{order.contact.name}</p>
            <a href={`tel:${order.contact.phone}`} className="flex items-center gap-2 text-sm text-blue-600">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              {order.contact.phone}
            </a>
            <a href={`mailto:${order.contact.email}`} className="flex items-center gap-2 text-sm text-blue-600">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              {order.contact.email}
            </a>
          </div>
        </div>

        {/* Problema */}
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

        {/* Equipo */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <h2 className="text-sm font-semibold text-gray-900 mb-3">Equipo</h2>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-gray-600">Marca:</span>
              <span className="font-medium text-gray-900">{order.equipment.brand}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Modelo:</span>
              <span className="font-medium text-gray-900">{order.equipment.model}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Serial:</span>
              <span className="font-mono text-xs text-gray-900">{order.equipment.serial}</span>
            </div>
          </div>
        </div>

        {/* Acciones */}
        {order.status === 'pendiente' && (
          <div className="sticky bottom-4">
            <button
              onClick={() => router.push(`/parte/${order.id}`)}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-lg shadow-lg transition-colors"
            >
              Completar Parte de Trabajo
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
