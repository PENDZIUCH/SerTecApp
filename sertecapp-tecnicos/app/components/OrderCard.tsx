'use client';

import { FC } from 'react';

type Priority = 'urgente' | 'alta' | 'media' | 'baja';
type Status = 'pendiente' | 'en_progreso' | 'completado';

interface OrderCardProps {
  id: number;
  clientName: string;
  problem: string;
  address: string;
  priority: Priority;
  status: Status;
  rejectedNote?: string | null;
  suggestedParts?: Array<{ id: number; name: string; stock: number }>;
  created_at?: string;
  completed_at?: string;
  scheduled_date?: string;
  notes?: string;
  onStart: () => void;
  onViewDetail: () => void;
}

const priorityConfig = {
  urgente: { container: 'border-l-4 border-red-500', badge: 'bg-red-500 text-white', label: 'Urgente' },
  alta:    { container: 'border-l-4 border-orange-500', badge: 'bg-orange-500 text-white', label: 'Alta' },
  media:   { container: 'border-l-4 border-yellow-500', badge: 'bg-yellow-500 text-white', label: 'Media' },
  baja:    { container: 'border-l-4 border-gray-400', badge: 'bg-gray-500 text-white', label: 'Baja' },
};

const statusConfig = {
  pendiente:   { label: 'Pendiente',  bg: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' },
  en_progreso: { label: 'En proceso', bg: 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
  completado:  { label: 'Completado', bg: 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300' },
};

export const OrderCard: FC<OrderCardProps> = ({
  id, clientName, problem, address, priority, status,
  rejectedNote, created_at, onStart, onViewDetail,
}) => {
  const ps = priorityConfig[priority] || priorityConfig['media'];
  const ss = statusConfig[status] || statusConfig['pendiente'];

  return (
    <div className={`${ps.container} bg-white/90 dark:bg-gray-800/90 rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 h-full flex flex-col`}>

      {/* Badges */}
      <div className="flex items-center gap-1.5 mb-2 flex-wrap">
        <span className={`${ps.badge} text-xs font-medium px-2.5 py-0.5 rounded-full`}>{ps.label}</span>
        <span className={`${ss.bg} text-xs font-medium px-2.5 py-0.5 rounded-full`}>{ss.label}</span>
      </div>

      {/* Cuerpo: dos columnas cuando hay nota de rechazo */}
      <div className={`${rejectedNote ? 'grid grid-cols-2 gap-3' : ''} mb-3 flex-1`}>

        {/* Columna izquierda: datos */}
        <div className="min-w-0">
          <h3 className="text-base font-semibold text-gray-900 dark:text-white leading-tight">{clientName}</h3>
          <p className="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{problem}</p>
          <div className="flex items-center gap-1 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
            <svg className="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{address}</span>
          </div>
        </div>

        {/* Columna derecha: nota de rechazo (solo si existe) */}
        {rejectedNote && (
          <div className="px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md flex flex-col justify-center">
            <p className="text-xs font-semibold text-red-700 dark:text-red-400 mb-1">❌ Rechazado</p>
            <p className="text-xs text-red-600 dark:text-red-300 line-clamp-4">{rejectedNote}</p>
          </div>
        )}
      </div>

      {/* Botones — siempre abajo, ancho completo */}
      <div className="flex gap-2">
        {(status === 'pendiente' || status === 'en_progreso' || rejectedNote) && (
          <button onClick={onStart}
            className={`flex-1 text-sm font-medium py-2.5 px-4 rounded-md text-white transition-colors ${rejectedNote ? 'bg-red-600 hover:bg-red-700' : status === 'en_progreso' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'}`}>
            {rejectedNote ? 'Corregir Parte' : status === 'en_progreso' ? 'Continuar Parte' : 'Crear Parte'}
          </button>
        )}
        <button onClick={onViewDetail}
          className="flex-1 text-sm font-medium py-2.5 px-4 rounded-md text-gray-700 dark:text-gray-200 bg-white/90 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
          Ver detalle
        </button>
      </div>

      {/* Footer */}
      <div className="text-xs text-gray-400 dark:text-gray-500 mt-2 text-right">
        #{String(id).padStart(4, '0')}
        {created_at && <span className="ml-2">Creada: {new Date(created_at).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>}
      </div>
    </div>
  );
};
