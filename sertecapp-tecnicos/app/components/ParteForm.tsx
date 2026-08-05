'use client';

import { useEffect, useState, useRef } from 'react';
import { saveParteLocal } from '../lib/storage';
import { useOnlineStatus } from '../../hooks/useOnlineStatus';
import { OfflineModal } from './ui/OfflineModal';
import { API_URL } from '../../lib/config';

function getGeoLocation(): Promise<{lat: number; lng: number} | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) { resolve(null); return; }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => resolve(null),
      { timeout: 15000, maximumAge: 60000 }
    );
  });
}

interface ParteFormProps {
  orderId: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function ParteForm({ orderId, onSuccess, onCancel }: ParteFormProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const { isOnline: effectiveOnline } = useOnlineStatus();

  const [diagnostico, setDiagnostico] = useState('');
  const [trabajoRealizado, setTrabajoRealizado] = useState('');
  const [repuestos, setRepuestos] = useState<Array<{nombre: string; cantidad: number}>>([]);
  const [nuevoRepuesto, setNuevoRepuesto] = useState('');
  const [cantidadRepuesto, setCantidadRepuesto] = useState(1);
  const [firma, setFirma] = useState<string | null>(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [toastMsg, setToastMsg] = useState('');
  const [toastType, setToastType] = useState<'success' | 'error'>('success');
  const [showOfflineModal, setShowOfflineModal] = useState(false);
  const [precachedGeo, setPrecachedGeo] = useState<{lat: number; lng: number} | null>(null);
  const [parteRechazado, setParteRechazado] = useState<{supervisor_notes: string; diagnosis?: string; work_done?: string} | null>(null);

  useEffect(() => {
    getGeoLocation().then(geo => { if (geo) setPrecachedGeo(geo); });
  }, []);

  useEffect(() => {
    if (!orderId) return;
    const token = localStorage.getItem('token');
    if (!token) return;
    fetch(`${API_URL}/api/v1/partes/${orderId}`, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
      if (data.success && data.data?.status === 'rejected') {
        setParteRechazado(data.data);
        if (data.data.diagnosis) setDiagnostico(data.data.diagnosis);
        if (data.data.work_done) setTrabajoRealizado(data.data.work_done);
      }
    }).catch(() => {});
  }, [orderId]);

  const showToast = (msg: string, type: 'success' | 'error') => {
    setToastMsg(msg); setToastType(type);
    setTimeout(() => setToastMsg(''), type === 'success' ? 2000 : 4000);
  };

  const startDrawing = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    e.preventDefault();
    setIsDrawing(true);
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const rect = canvas.getBoundingClientRect();
    const sx = canvas.width / rect.width;
    const sy = canvas.height / rect.height;
    const x = ('touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left) * sx;
    const y = ('touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top) * sy;
    ctx.beginPath(); ctx.moveTo(x, y);
  };

  const draw = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    e.preventDefault();
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const rect = canvas.getBoundingClientRect();
    const sx = canvas.width / rect.width;
    const sy = canvas.height / rect.height;
    const x = ('touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left) * sx;
    const y = ('touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top) * sy;
    ctx.lineTo(x, y);
    ctx.strokeStyle = '#000'; ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    ctx.stroke();
  };

  const stopDrawing = () => {
    setIsDrawing(false);
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    let pixels = 0;
    for (let i = 3; i < imageData.data.length; i += 4) {
      if (imageData.data[i] > 0) pixels++;
    }
    if (pixels > 50) setFirma(canvas.toDataURL());
    else setFirma(null);
  };

  const clearSignature = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    setFirma(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!diagnostico.trim() || !trabajoRealizado.trim()) { showToast('Completá el diagnóstico y el trabajo realizado', 'error'); return; }
    if (!firma) { showToast('La firma del cliente es obligatoria', 'error'); return; }
    if (!orderId) { showToast('Error: no hay ID de orden', 'error'); return; }
    if (saving) return;
    setSaving(true);

    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const geo = precachedGeo || await getGeoLocation();

    const parteData: any = {
      orden_id: parseInt(orderId),
      tecnico_id: user.id || 1,
      diagnostico,
      trabajo_realizado: trabajoRealizado,
      repuestos_usados: repuestos,
      firma_base64: firma,
      ...(geo && { lat: geo.lat, lng: geo.lng }),
    };

    if (effectiveOnline) {
      try {
        const token = localStorage.getItem('token');
        const response = await fetch(`${API_URL}/api/v1/partes`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(parteData),
        });
        const data = await response.json();
        if (response.ok && data.success) {
          localStorage.removeItem('sertecapp_ordenes_cache');
          showToast('Parte guardado exitosamente ✓', 'success');
          setTimeout(() => onSuccess(), 1500);
        } else {
          saveParteLocal(parteData);
          setShowOfflineModal(true);
        }
      } catch {
        saveParteLocal(parteData);
        setShowOfflineModal(true);
      }
    } else {
      saveParteLocal(parteData);
      setShowOfflineModal(true);
    }
    setSaving(false);
  };

  return (
    <div className="flex flex-col h-full">
      {parteRechazado && (
        <div className="mx-4 mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
          <p className="text-sm font-semibold text-red-700 dark:text-red-400">❌ Parte rechazado — corregí los errores</p>
          <p className="text-sm text-red-600 dark:text-red-300 mt-1">{parteRechazado.supervisor_notes}</p>
        </div>
      )}

      <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-4 py-4 space-y-4">
        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <label className="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Diagnóstico *</label>
          <textarea value={diagnostico} onChange={(e) => setDiagnostico(e.target.value)} required rows={3}
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500"
            placeholder="Describe qué encontraste..." />
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <label className="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Trabajo Realizado *</label>
          <textarea value={trabajoRealizado} onChange={(e) => setTrabajoRealizado(e.target.value)} required rows={3}
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500"
            placeholder="Describe qué hiciste..." />
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <label className="block text-sm font-semibold text-gray-900 dark:text-white mb-3">Repuestos Utilizados</label>
          {repuestos.length > 0 && (
            <div className="space-y-2 mb-3">
              {repuestos.map((rep, i) => (
                <div key={i} className="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 rounded-lg">
                  <span className="text-sm text-gray-900 dark:text-white">{rep.nombre} ×{rep.cantidad}</span>
                  <button type="button" onClick={() => setRepuestos(repuestos.filter((_, j) => j !== i))}
                    className="text-red-500 hover:text-red-700 text-xs">✕</button>
                </div>
              ))}
            </div>
          )}
          <div className="flex gap-2">
            <input type="text" value={nuevoRepuesto} onChange={(e) => setNuevoRepuesto(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), nuevoRepuesto.trim() && (setRepuestos([...repuestos, { nombre: nuevoRepuesto, cantidad: cantidadRepuesto }]), setNuevoRepuesto(''), setCantidadRepuesto(1)))}
              className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Repuesto..." />
            <input type="number" value={cantidadRepuesto} onChange={(e) => setCantidadRepuesto(parseInt(e.target.value) || 1)} min="1"
              className="w-16 px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-center bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
            <button type="button" onClick={() => nuevoRepuesto.trim() && (setRepuestos([...repuestos, { nombre: nuevoRepuesto, cantidad: cantidadRepuesto }]), setNuevoRepuesto(''), setCantidadRepuesto(1))}
              className="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">+</button>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <label className="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            Firma del Cliente * {firma && <span className="text-green-600 text-xs ml-2">✓ Capturada</span>}
          </label>
          <div className="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden">
            <canvas
              ref={(el) => { (canvasRef as React.MutableRefObject<HTMLCanvasElement|null>).current = el; if (el) { const ctx = el.getContext('2d'); if (ctx) { ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, el.width, el.height); } } }}
              width={600} height={180}
              style={{ width: '100%', height: 'auto', aspectRatio: '10/3', backgroundColor: '#ffffff', touchAction: 'none', display: 'block' }}
              onMouseDown={startDrawing} onMouseMove={draw} onMouseUp={stopDrawing} onMouseLeave={stopDrawing}
              onTouchStart={startDrawing} onTouchMove={draw} onTouchEnd={stopDrawing}
            />
          </div>
          <button type="button" onClick={clearSignature} className="mt-2 w-full text-sm text-blue-600 py-1.5 border border-blue-300 rounded-lg hover:bg-blue-50">
            Limpiar firma
          </button>
        </div>

        <div className="flex gap-3 pb-4">
          <button type="button" onClick={onCancel}
            className="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
            Cancelar
          </button>
          <button type="submit" disabled={saving}
            className="flex-2 flex-grow-[2] py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg font-semibold">
            {saving ? 'Guardando...' : effectiveOnline ? 'Guardar Parte' : 'Guardar Local'}
          </button>
        </div>
      </form>

      {toastMsg && (
        <div className={`fixed bottom-6 left-4 right-4 z-50 rounded-lg shadow-2xl p-4 ${toastType === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white`}>
          <p className="font-medium">{toastMsg}</p>
        </div>
      )}
      <OfflineModal isOpen={showOfflineModal} onClose={() => { setShowOfflineModal(false); onSuccess(); }} />
    </div>
  );
}
