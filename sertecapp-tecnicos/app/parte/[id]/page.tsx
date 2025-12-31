'use client';

import { useRouter, useParams } from 'next/navigation';
import { useEffect, useState, useRef } from 'react';
import { saveParteLocal, isOnline } from '../../lib/storage';

export default function PartePage() {
  const router = useRouter();
  const params = useParams();
  const canvasRef = useRef<HTMLCanvasElement>(null);
  
  const [diagnostico, setDiagnostico] = useState('');
  const [trabajoRealizado, setTrabajoRealizado] = useState('');
  const [repuestos, setRepuestos] = useState<Array<{nombre: string; cantidad: number}>>([]);
  const [nuevoRepuesto, setNuevoRepuesto] = useState('');
  const [cantidadRepuesto, setCantidadRepuesto] = useState(1);
  const [firma, setFirma] = useState<string | null>(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [online, setOnline] = useState(true);

  useEffect(() => {
    setOnline(isOnline());
  }, []);

  const startDrawing = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    setIsDrawing(true);
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = 'touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = 'touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top;

    ctx.beginPath();
    ctx.moveTo(x, y);
  };

  const draw = (e: React.TouchEvent<HTMLCanvasElement> | React.MouseEvent<HTMLCanvasElement>) => {
    if (!isDrawing) return;

    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = 'touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = 'touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top;

    ctx.lineTo(x, y);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.stroke();
  };

  const stopDrawing = () => {
    setIsDrawing(false);
    const canvas = canvasRef.current;
    if (canvas) {
      setFirma(canvas.toDataURL());
    }
  };

  const clearSignature = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    setFirma(null);
  };

  const agregarRepuesto = () => {
    if (!nuevoRepuesto.trim()) return;

    setRepuestos([...repuestos, {
      nombre: nuevoRepuesto,
      cantidad: cantidadRepuesto
    }]);
    setNuevoRepuesto('');
    setCantidadRepuesto(1);
  };

  const eliminarRepuesto = (index: number) => {
    setRepuestos(repuestos.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!diagnostico.trim() || !trabajoRealizado.trim()) {
      alert('Por favor completá el diagnóstico y el trabajo realizado');
      return;
    }

    if (!firma) {
      alert('Por favor agregá la firma del cliente');
      return;
    }

    setSaving(true);

    try {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      
      // Guardar localmente (funciona offline)
      const parte = saveParteLocal({
        orden_id: parseInt(params.id as string),
        tecnico_id: user.id || 1,
        diagnostico,
        trabajo_realizado: trabajoRealizado,
        repuestos_usados: repuestos,
        firma_base64: firma,
      });

      alert(
        online 
          ? '✅ Parte guardado exitosamente' 
          : '✅ Parte guardado localmente. Se sincronizará cuando vuelva la conexión.'
      );

      router.push('/ordenes');
    } catch (error) {
      console.error('Error guardando parte:', error);
      alert('❌ Error al guardar el parte');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
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
            <h1 className="text-base font-semibold text-gray-900">Parte de Trabajo</h1>
            <p className="text-sm text-gray-500">Orden #{params.id}</p>
          </div>
        </div>
      </header>

      {/* Form */}
      <form onSubmit={handleSubmit} className="max-w-7xl mx-auto px-4 py-6 space-y-4">
        {/* Diagnóstico */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">
            Diagnóstico *
          </label>
          <textarea
            value={diagnostico}
            onChange={(e) => setDiagnostico(e.target.value)}
            required
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe qué encontraste..."
          />
        </div>

        {/* Trabajo Realizado */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">
            Trabajo Realizado *
          </label>
          <textarea
            value={trabajoRealizado}
            onChange={(e) => setTrabajoRealizado(e.target.value)}
            required
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe qué hiciste..."
          />
        </div>

        {/* Repuestos */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-3">
            Repuestos Utilizados
          </label>
          
          {/* Lista de repuestos */}
          {repuestos.length > 0 && (
            <div className="space-y-2 mb-3">
              {repuestos.map((rep, index) => (
                <div key={index} className="flex items-center justify-between bg-gray-50 p-2 rounded-lg">
                  <span className="text-sm text-gray-900">{rep.nombre} ×{rep.cantidad}</span>
                  <button
                    type="button"
                    onClick={() => eliminarRepuesto(index)}
                    className="text-red-600 hover:text-red-700"
                  >
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              ))}
            </div>
          )}

          {/* Agregar repuesto */}
          <div className="flex gap-2">
            <input
              type="text"
              value={nuevoRepuesto}
              onChange={(e) => setNuevoRepuesto(e.target.value)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
              placeholder="Nombre del repuesto"
            />
            <input
              type="number"
              value={cantidadRepuesto}
              onChange={(e) => setCantidadRepuesto(parseInt(e.target.value) || 1)}
              min="1"
              className="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center"
            />
            <button
              type="button"
              onClick={agregarRepuesto}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
            >
              +
            </button>
          </div>
        </div>

        {/* Firma */}
        <div className="bg-white rounded-lg p-4 border border-gray-200">
          <label className="block text-sm font-semibold text-gray-900 mb-2">
            Firma del Cliente *
          </label>
          <div className="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-gray-50">
            <canvas
              ref={canvasRef}
              width={400}
              height={200}
              className="w-full touch-none bg-white rounded"
              onMouseDown={startDrawing}
              onMouseMove={draw}
              onMouseUp={stopDrawing}
              onMouseLeave={stopDrawing}
              onTouchStart={startDrawing}
              onTouchMove={draw}
              onTouchEnd={stopDrawing}
            />
          </div>
          <button
            type="button"
            onClick={clearSignature}
            className="mt-2 text-sm text-blue-600 hover:text-blue-700"
          >
            Limpiar firma
          </button>
        </div>

        {/* Submit */}
        <div className="sticky bottom-4">
          <button
            type="submit"
            disabled={saving}
            className="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-4 rounded-lg shadow-lg transition-colors"
          >
            {saving ? 'Guardando...' : online ? 'Guardar Parte' : 'Guardar Localmente'}
          </button>
        </div>
      </form>
    </div>
  );
}
