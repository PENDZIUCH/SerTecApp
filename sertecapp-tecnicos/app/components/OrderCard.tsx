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
  suggestedParts?: Array<{ id: number; name: string; stock: number }>;
  onStart: () => void;
  onViewDetail: () => void;
}

const priorityConfig = {
  urgente: {
    container: 'bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-l-4 border-red-500',
    badge: 'bg-red-500 text-white',
    dot: 'bg-red-500',
    label: 'Urgente',
  },
  alta: {
    container: 'bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-l-4 border-orange-500',
    badge: 'bg-orange-500 text-white',
    dot: 'bg-orange-500',
    label: 'Alta',
  },
  media: {
    container: 'bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-l-4 border-yellow-500',
    badge: 'bg-yellow-500 text-white',
    dot: 'bg-yellow-500',
    label: 'Media',
  },
  baja: {
    container: 'bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-l-4 border-gray-400',
    badge: 'bg-gray-500 text-white',
    dot: 'bg-gray-400',
    label: 'Baja',
    },
};

const statusConfig = {
  pendiente: { label: 'Pendiente', color: 'text-gray-600', bg: 'bg-gray-100' },
  en_progreso: { label: 'En proceso', color: 'text-blue-700', bg: 'bg-blue-50' },
  completado: { label: 'Completado', color: 'text-green-700', bg: 'bg-green-50' },
};

export const OrderCard: FC<OrderCardProps> = ({
  id,
  clientName,
  problem,
  address,
  priority,
  status,
  suggestedParts = [],
  onStart,
  onViewDetail,
}) => {
  const priorityStyle = priorityConfig[priority];
  const statusStyle = statusConfig[status];

  return (
    <div
      className={`${priorityStyle.container} rounded-lg shadow-sm hover:shadow-md transition-shadow p-4`}
    >
      {/* Header */}
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-2">
            <span className={`${priorityStyle.badge} text-xs font-medium px-2.5 py-0.5 rounded-full`}>
              {priorityStyle.label}
            </span>
            <span className={`${statusStyle.bg} ${statusStyle.color} text-xs font-medium px-2.5 py-0.5 rounded-full`}>
              {statusStyle.label}
            </span>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">{clientName}</h3>
          <p className="text-sm text-gray-600 dark:text-gray-300">{problem}</p>
        </div>
      </div>

      {/* Address */}
      <div className="flex items-start gap-2 mb-3 text-sm text-gray-500 dark:text-gray-400">
        <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>{address}</span>
      </div>

      {/* Suggested Parts */}
      {suggestedParts.length > 0 && (
        <div className="mb-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
          <p className="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Repuestos sugeridos</p>
          <div className="flex flex-wrap gap-1.5">
            {suggestedParts.map((part) => (
              <span
                key={part.id}
                className="inline-flex items-center text-xs bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-2 py-1 rounded border border-gray-200 dark:border-gray-500"
              >
                {part.name}
                <span className="ml-1 text-gray-500">×{part.stock}</span>
              </span>
            ))}
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="flex gap-2 pt-2">
        {status === 'pendiente' && (
          <button
            onClick={onStart}
            className="flex-1 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium py-2.5 px-4 rounded-md transition-colors"
          >
            Iniciar
          </button>
        )}
        {status === 'en_progreso' && (
          <button
            onClick={onStart}
            className="flex-1 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-medium py-2.5 px-4 rounded-md transition-colors"
          >
            Completar
          </button>
        )}
        <button
          onClick={onViewDetail}
          className="flex-1 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 active:bg-gray-100 dark:active:bg-gray-500 text-gray-700 dark:text-gray-200 text-sm font-medium py-2.5 px-4 rounded-md border border-gray-300 dark:border-gray-600 transition-colors"
        >
          Ver detalle
        </button>
      </div>

      {/* Order ID */}
      <p className="text-xs text-gray-400 dark:text-gray-500 mt-3 text-right">
        #{id.toString().padStart(4, '0')}
      </p>
    </div>
  );
};
