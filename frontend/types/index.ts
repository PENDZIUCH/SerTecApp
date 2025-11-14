// SerTecApp - TypeScript Types
// Tipos compartidos para toda la aplicación

export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: 'admin' | 'tecnico' | 'supervisor';
  activo: boolean;
  created_at: string;
  updated_at: string;
}

export interface Cliente {
  id: number;
  nombre: string;
  razon_social?: string;
  cuit?: string;
  tipo: 'abonado' | 'esporadico';
  frecuencia_visitas: number;
  direccion?: string;
  localidad?: string;
  provincia?: string;
  codigo_postal?: string;
  telefono?: string;
  email?: string;
  contacto_nombre?: string;
  contacto_telefono?: string;
  estado: 'activo' | 'inactivo' | 'moroso';
  notas?: string;
  created_at: string;
  updated_at: string;
}

export interface ConfigFrecuencia {
  id: number;
  frecuencia_visitas: number;
  nombre: string;
  color_hex: string;
  color_nombre: string;
  activo: boolean;
  orden: number;
  created_at: string;
  updated_at: string;
}

export interface Abono {
  id: number;
  cliente_id: number;
  frecuencia_visitas: number;
  monto_mensual: number;
  fecha_inicio: string;
  fecha_fin?: string;
  estado: 'activo' | 'suspendido' | 'finalizado';
  observaciones?: string;
  created_at: string;
  updated_at: string;
  // Relations
  cliente?: Cliente;
  config_frecuencia?: ConfigFrecuencia;
}

export interface OrdenTrabajo {
  id: number;
  numero_parte: string;
  cliente_id: number;
  tecnico_id: number;
  fecha_trabajo: string;
  hora_inicio?: string;
  hora_fin?: string;
  equipo_marca?: string;
  equipo_modelo?: string;
  equipo_serie?: string;
  descripcion_trabajo: string;
  observaciones?: string;
  estado: 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';
  firma_cliente?: string;
  total: number;
  sincronizado: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  cliente?: Cliente;
  tecnico?: Usuario;
  repuestos?: OrdenRepuesto[];
}

export interface Repuesto {
  id: number;
  codigo: string;
  descripcion: string;
  categoria?: string;
  marca?: string;
  stock_actual: number;
  stock_minimo: number;
  precio_unitario: number;
  proveedor?: string;
  ubicacion?: string;
  activo: boolean;
  created_at: string;
  updated_at: string;
}

export interface OrdenRepuesto {
  id: number;
  orden_trabajo_id: number;
  repuesto_id: number;
  cantidad: number;
  precio_unitario: number;
  subtotal: number;
  created_at: string;
  // Relations
  repuesto?: Repuesto;
}

export interface TallerEquipo {
  id: number;
  cliente_id?: number;
  origen?: string;
  equipo_marca?: string;
  equipo_modelo?: string;
  equipo_serie?: string;
  fecha_ingreso: string;
  fecha_salida?: string;
  estado: 'en_taller' | 'esperando_repuesto' | 'reparado' | 'entregado';
  diagnostico?: string;
  reparacion_realizada?: string;
  observaciones?: string;
  tecnico_responsable_id?: number;
  costo_reparacion?: number;
  created_at: string;
  updated_at: string;
  // Relations
  cliente?: Cliente;
  tecnico_responsable?: Usuario;
}

export interface Factura {
  id: number;
  cliente_id: number;
  numero_factura?: string;
  tipo: 'A' | 'B' | 'C';
  fecha_emision: string;
  total: number;
  estado: 'pendiente' | 'enviada_tango' | 'aprobada' | 'error';
  tango_response?: string;
  orden_trabajo_id?: number;
  abono_id?: number;
  created_at: string;
  updated_at: string;
  // Relations
  cliente?: Cliente;
  items?: FacturaItem[];
}

export interface FacturaItem {
  id: number;
  factura_id: number;
  descripcion: string;
  cantidad: number;
  precio_unitario: number;
  subtotal: number;
  created_at: string;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
