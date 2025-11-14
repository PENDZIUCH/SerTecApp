// SerTecApp - Offline Sync Service
// Maneja sincronización entre IndexedDB local y backend

import { openDB, DBSchema, IDBPDatabase } from 'idb';

interface SerTecDB extends DBSchema {
  clientes: { key: number; value: any };
  ordenes: { key: number; value: any; indexes: { 'sincronizado': boolean } };
  repuestos: { key: number; value: any };
  sync_queue: { key: number; value: SyncQueueItem; indexes: { 'sincronizado': boolean } };
}

interface SyncQueueItem {
  id?: number;
  tabla: string;
  registro_id: number;
  accion: 'create' | 'update' | 'delete';
  datos: any;
  sincronizado: boolean;
  timestamp: number;
}

class OfflineSyncService {
  private db: IDBPDatabase<SerTecDB> | null = null;
  private readonly DB_NAME = 'sertecapp_db';
  private readonly DB_VERSION = 1;

  async init() {
    this.db = await openDB<SerTecDB>(this.DB_NAME, this.DB_VERSION, {
      upgrade(db) {
        // Stores principales
        if (!db.objectStoreNames.contains('clientes')) {
          db.createObjectStore('clientes', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('ordenes')) {
          const ordenesStore = db.createObjectStore('ordenes', { keyPath: 'id' });
          ordenesStore.createIndex('sincronizado', 'sincronizado');
        }
        if (!db.objectStoreNames.contains('repuestos')) {
          db.createObjectStore('repuestos', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('sync_queue')) {
          const syncStore = db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
          syncStore.createIndex('sincronizado', 'sincronizado');
        }
      },
    });
    
    // Setup online/offline listeners
    window.addEventListener('online', () => this.onOnline());
    window.addEventListener('offline', () => this.onOffline());
    
    return this.db;
  }

  // Detecta si hay conexión
  isOnline(): boolean {
    return navigator.onLine;
  }

  // Guarda datos localmente
  async saveLocal(tabla: string, data: any) {
    if (!this.db) await this.init();
    
    const tx = this.db!.transaction(tabla as any, 'readwrite');
    await tx.store.put(data);
    
    // Agregar a cola de sincronización
    await this.addToSyncQueue(tabla, data.id, 'update', data);
  }

  // Agrega item a cola de sincronización
  async addToSyncQueue(tabla: string, registro_id: number, accion: 'create' | 'update' | 'delete', datos: any) {
    if (!this.db) await this.init();
    
    const item: SyncQueueItem = {
      tabla,
      registro_id,
      accion,
      datos,
      sincronizado: false,
      timestamp: Date.now()
    };
    
    await this.db!.add('sync_queue', item);
  }

  // Sincroniza cola con backend
  async syncWithBackend() {
    if (!this.isOnline() || !this.db) return;
    
    const tx = this.db.transaction('sync_queue', 'readwrite');
    const index = tx.store.index('sincronizado');
    const pendientes = await index.getAll(false);
    
    for (const item of pendientes) {
      try {
        // Enviar al backend según la acción
        const endpoint = `/api/${item.tabla}`;
        let response;
        
        switch (item.accion) {
          case 'create':
            response = await fetch(endpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(item.datos)
            });
            break;
          case 'update':
            response = await fetch(`${endpoint}/${item.registro_id}`, {
              method: 'PUT',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(item.datos)
            });
            break;
          case 'delete':
            response = await fetch(`${endpoint}/${item.registro_id}`, {
              method: 'DELETE'
            });
            break;
        }
        
        if (response?.ok) {
          // Marcar como sincronizado
          await this.db.put('sync_queue', { ...item, sincronizado: true });
        }
      } catch (error) {
        console.error('Error syncing item:', error);
      }
    }
  }

  // Listeners de conexión
  private async onOnline() {
    console.log('🟢 Conexión restaurada - Sincronizando...');
    await this.syncWithBackend();
  }

  private onOffline() {
    console.log('🔴 Sin conexión - Modo offline activado');
  }

  // Obtener datos pendientes de sincronización
  async getPendingSync() {
    if (!this.db) await this.init();
    const tx = this.db!.transaction('sync_queue', 'readonly');
    const index = tx.store.index('sincronizado');
    return await index.getAll(false);
  }
}

export const offlineSync = new OfflineSyncService();
