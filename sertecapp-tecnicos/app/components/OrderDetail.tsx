'use client';

interface OrderDetailProps {
  order: {
    id: number;
    clientName: string;
    problem: string;
    address: string;
    priority: 'urgente' | 'alta' | 'media' | 'baja';
    status: 'pendiente' | 'en_progreso' | 'completado';
    suggestedParts?: Array<{ id: number; name: string; stock: number }>;
  };
}

export const OrderDetail: React.FC<OrderDetailProps> = ({ order }) => {

  const priorityColors = {
    urgente: 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400',
    alta: 'text-orange-600 bg-orange-50 dark:bg-orange-900/20 dark:text-orange-400',
    media: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400',
    baja: 'text-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-400',
  };

  return (
    <div className="p-6 space-y-4">
      {/* Cliente */}
      <div>
        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Cliente</h3>
        <p className="text-lg font-semibold text-gray-900 dark:text-white">{order.clientName}</p>
      </div>

      {/* Prioridad */}
      <div>
        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Prioridad</h3>
        <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${priorityColors[order.priority as keyof typeof priorityColors]}`}>
          {order.priority.toUpperCase()}
        </span>
      </div>

      {/* Problema */}
      <div>
        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Problema</h3>
        <p className="text-gray-900 dark:text-gray-100">{order.problem}</p>
      </div>

      {/* Dirección */}
      <div>
        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Dirección</h3>
        <p className="text-gray-900 dark:text-gray-100 flex items-start gap-2">
          <svg className="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          </svg>
          {order.address}
        </p>
      </div>

      {/* Repuestos sugeridos */}
      {order.suggestedParts && order.suggestedParts.length > 0 && (
        <div>
          <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Repuestos sugeridos</h3>
          <div className="space-y-2">
            {order.suggestedParts.map((part: any) => (
              <div 
                key={part.id}
                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
              >
                <span className="text-gray-900 dark:text-gray-100">{part.name}</span>
                <span className="text-sm text-gray-500 dark:text-gray-400">Stock: {part.stock}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Estado */}
      <div>
        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Estado</h3>
        <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${
          order.status === 'completado' 
            ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400' 
            : 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400'
        }`}>
          {order.status === 'completado' ? 'Completado' : 'Pendiente'}
        </span>
      </div>
    </div>
  );
};
