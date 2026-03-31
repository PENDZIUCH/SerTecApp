'use client';

import { useRouter, useSearchParams } from 'next/navigation';
import { useEffect, useState, useRef, Suspense } from 'react';
import { saveParteLocal } from '../lib/storage';
import { useOnlineStatus } from '../../hooks/useOnlineStatus';
import { OfflineModal } from '../components/ui/OfflineModal';

function ParteContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const orderId = searchParams.get('id') || '';
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
  const [showToast, setShowToast] = useState(false);
  const [toastMessage, setToastMessage] = useState('');
  const [toastType, setToastType] = useState<'success' | 'error'>('success');
  const [showOfflineModal, setShowOfflineModal] = useState(false);

  const startDrawing = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    e.preventDefault();
    setIsDrawing(true);
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const x = ('touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left) * scaleX;
    const y = ('touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top) * scaleY;
    ctx.beginPath();
    ctx.moveTo(x, y);
  };

  const draw = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    e.preventDefault();
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const x = ('touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left) * scaleX;
    const y = ('touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top) * scaleY;
    ctx.lineTo(x, y);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.stroke();
  };

  const stopDrawing = () => {
    setIsDrawing(false);
    const canvas = canvasRef.current;
    if (canvas) setFirma(canvas.toDataURL());
  };

  const clearSignature = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    setFirma(null);
  };

  const showSuccessToast = (message: string) => {
    setToastMessage(message); setToastType('success'); setShowToast(true);
    setTimeout(() => setShowToast(false), 3000);
  };

  const showErrorToast = (message: string) => {
    setToastMessage(message); setToastType('error'); setShowToast(true);
    setTimeout(() => setShowToast(false), 3000);
  };

  const agregarRepuesto = () => {
    if (!nuevoRepuesto.trim()) return;
    setRepuestos([...repuestos, { nombre: nuevoRepuesto, cantidad: cantidadRepuesto }]);
    setNuevoRepuesto(''); setCantidadRepuesto(1);
  };

  const eliminarRepuesto = (index: number) => setRepuestos(repuestos.filter((_, i) => i !== index));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!diagnostico.trim() || !trabajoRealizado.trim()) { showErrorToast('Completá el diagnóstico y el trabajo realizado'); return; }
    if (!firma) { showErrorToast('Falta la firma del cliente'); return; }
    if (saving) return;
    setSaving(true);
    try {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      if (effectiveOnline) {
        try {
          const token = localStorage.getItem('token');
          const response = await fetch('https://sertecapp.pendziuch.com/api/v1/partes', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ orden_id: parseInt(orderId), tecnico_id: user.id || 1, diagnostico, trabajo_realizado: trabajoRealizado, repuestos_usados: repuestos, firma_base64: firma })
          });
          if (response.ok) {
            localStorage.removeItem('sertecapp_ordenes_cache');
            showSuccessToast('Parte guardado exitosamente');
          } else {
            saveParteLocal({ orden_id: parseInt(orderId), tecnico_id: user.id || 1, diagnostico, trabajo_realizado: trabajoRealizado, repuestos_usados: repuestos, firma_base64: firma });
            setShowOfflineModal(true);
          }
        } catch {
          saveParteLocal({ orden_id: parseInt(orderId), tecnico_id: user.id || 1, diagnostico, trabajo_realizado: trabajoRealizado, repuestos_usados: repuestos, firma_base64: firma });
          setShowOfflineModal(true);
        }
      } else {
        saveParteLocal({ orden_id: parseInt(orderId), tecnico_id: user.id || 1, diagnostico, trabajo_realizado: trabajoRealizado, repuestos_usados: repuestos, firma_base64: firma });
        setShowOfflineModal(true);
      }
      setTimeout(() => router.push('/ordenes'), 1500);
    } catch { showErrorToast('Error al guardar el parte'); }
    finally { setSaving(false); }
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center gap-3">
          <button onClick={() => router.back()} className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg className="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 className="text-base font-semibold text-gray-900">Parte de Trabajo</h1>
            <p className="text-sm text-gray-500">Orden #{orderId}</p>
          </div>
        </div>
      </header>

      <form onSubmit={handleSubmit} className="max-w-7xl mx-auto px-4 py-6 space-y-4">
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">Diagnóstico *</label>
          <textarea value={diagnostico} onChange={(e) => setDiagnostico(e.target.value)} required rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe qué encontraste..." />
        </div>

        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">Trabajo Realizado *</label>
          <textarea value={trabajoRealizado} onChange={(e) => setTrabajoRealizado(e.target.value)} required rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe qué hiciste..." />
        </div>

        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-3">Repuestos Utilizados</label>
          {repuestos.length > 0 && (
            <div className="space-y-2 mb-3">
              {repuestos.map((rep, index) => (
                <div key={index} className="flex items-center justify-between bg-gray-50 p-2 rounded-lg">
                  <span className="text-sm text-gray-900">{rep.nombre} ×{rep.cantidad}</span>
                  <button type="button" onClick={() => eliminarRepuesto(index)} className="text-red-600 hover:text-red-700">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              ))}
            </div>
          )}
          <div className="space-y-2">
            <input type="text" value={nuevoRepuesto} onChange={(e) => setNuevoRepuesto(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), agregarRepuesto())}
              className="w-full px-3 py-3 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Nombre del repuesto" />
            <div className="flex gap-2">
              <input type="number" value={cantidadRepuesto} onChange={(e) => setCantidadRepuesto(parseInt(e.target.value) || 1)} min="1"
                className="flex-1 px-3 py-3 border border-gray-300 rounded-lg text-sm text-center text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <button type="button" onClick={agregarRepuesto} disabled={!nuevoRepuesto.trim()}
                className="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 disabled:bg-gray-300 transition-colors">
                Agregar
              </button>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">Firma del Cliente *</label>
          <div className="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50">
            <canvas ref={canvasRef} width={600} height={200}
              className="w-full touch-none bg-white"
              style={{ maxWidth: '100%', height: 'auto', aspectRatio: '3/1' }}
              onMouseDown={startDrawing} onMouseMove={draw} onMouseUp={stopDrawing} onMouseLeave={stopDrawing}
              onTouchStart={startDrawing} onTouchMove={draw} onTouchEnd={stopDrawing} />
          </div>
          <button type="button" onClick={clearSignature}
            className="mt-3 w-full text-sm text-blue-600 font-medium py-2 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
            Limpiar firma
          </button>
        </div>

        <div className="sticky bottom-4">
          <button type="submit" disabled={saving}
            className="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-4 rounded-lg shadow-lg transition-colors">
            {saving ? 'Guardando...' : effectiveOnline ? 'Guardar Parte' : 'Guardar Localmente'}
          </button>
        </div>
      </form>

      {showToast && (
        <div className="fixed bottom-6 left-4 right-4 z-50">
          <div className={`rounded-lg shadow-2xl p-4 flex items-center gap-3 ${toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`}>
            <p className="font-medium">{toastMessage}</p>
          </div>
        </div>
      )}
      <OfflineModal isOpen={showOfflineModal} onClose={() => { setShowOfflineModal(false); router.push('/ordenes'); }} />
    </div>
  );
}

export default function PartePage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-gray-50 flex items-center justify-center"><div className="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>}>
      <ParteContent />
    </Suspense>
  );
}
