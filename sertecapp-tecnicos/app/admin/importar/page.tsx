'use client';

import { useEffect, useState, useRef } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../../../lib/config';

type ImportType = 'clientes' | 'repuestos';
type RowStatus = 'pending' | 'ok' | 'error' | 'skip';

interface PreviewRow {
  data: Record<string, any>;
  status: RowStatus;
  message?: string;
}

export default function ImportarPage() {
  const router = useRouter();
  const [token, setToken] = useState('');
  const [importType, setImportType] = useState<ImportType>('clientes');
  const [preview, setPreview] = useState<PreviewRow[]>([]);
  const [headers, setHeaders] = useState<string[]>([]);
  const [importing, setImporting] = useState(false);
  const [progress, setProgress] = useState(0);
  const [done, setDone] = useState(false);
  const [stats, setStats] = useState({ ok: 0, error: 0, skip: 0 });
  const [fileName, setFileName] = useState('');
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    if (!u?.roles?.includes('administrador') && !u?.roles?.includes('admin')) { router.push('/ordenes'); return; }
    setToken(t);
  }, []);

  const handleFile = async (file: File) => {
    setFileName(file.name);
    setPreview([]); setDone(false); setProgress(0);
    setStats({ ok: 0, error: 0, skip: 0 });

    // Cargar SheetJS dinámicamente
    const XLSX = await import('xlsx');
    const buffer = await file.arrayBuffer();
    const wb = XLSX.read(buffer, { type: 'array' });
    const ws = wb.Sheets[wb.SheetNames[0]];
    const rows: any[] = XLSX.utils.sheet_to_json(ws, { defval: '' });

    if (rows.length === 0) return;
    setHeaders(Object.keys(rows[0]));

    // Mapear filas a formato de preview
    const mapped: PreviewRow[] = rows.map(row => {
      if (importType === 'clientes') {
        const nombre = row['razon_social'] || row['business_name'] || row['nombre'] || row['name'] || row['Razon Social'] || row['Razón Social'] || '';
        if (!nombre.toString().trim()) return { data: row, status: 'skip', message: 'Sin nombre' };
        return {
          data: {
            customer_type: 'company',
            business_name: nombre.toString().trim(),
            email: (row['email'] || row['Email'] || row['mail'] || '').toString().trim() || null,
            phone: (row['telefono'] || row['phone'] || row['Telefono'] || row['Teléfono'] || '').toString().trim() || null,
            tax_id: (row['cuit'] || row['CUIT'] || row['tax_id'] || row['dni'] || '').toString().trim() || null,
            address: (row['direccion'] || row['address'] || row['Direccion'] || row['Dirección'] || '').toString().trim() || null,
            city: (row['ciudad'] || row['city'] || row['Ciudad'] || '').toString().trim() || null,
          },
          status: 'pending'
        };
      } else {
        // repuestos
        const name = row['nombre'] || row['name'] || row['descripcion'] || row['Nombre'] || row['Descripcion'] || '';
        if (!name.toString().trim()) return { data: row, status: 'skip', message: 'Sin nombre' };
        return {
          data: {
            name: name.toString().trim(),
            part_number: (row['codigo'] || row['code'] || row['part_number'] || row['Codigo'] || '').toString().trim() || null,
            sku: (row['sku'] || row['SKU'] || '').toString().trim() || null,
            unit_cost: parseFloat(row['costo'] || row['cost'] || row['unit_cost'] || '0') || 0,
            stock_qty: parseInt(row['stock'] || row['cantidad'] || '0') || 0,
            description: (row['descripcion'] || row['description'] || '').toString().trim() || null,
          },
          status: 'pending'
        };
      }
    });

    setPreview(mapped);
  };

  const importar = async () => {
    const pendientes = preview.filter(r => r.status === 'pending');
    if (pendientes.length === 0) return;
    setImporting(true); setProgress(0);
    const updated = [...preview];
    let ok = 0, error = 0, skip = preview.filter(r => r.status === 'skip').length;

    for (let i = 0; i < updated.length; i++) {
      if (updated[i].status !== 'pending') continue;
      try {
        const endpoint = importType === 'clientes' ? '/api/v1/customers' : '/api/v1/parts';
        const res = await fetch(`${API_URL}${endpoint}`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(updated[i].data),
        });
        if (res.ok) { updated[i].status = 'ok'; ok++; }
        else {
          const d = await res.json();
          updated[i].status = 'error';
          updated[i].message = d.message || `Error ${res.status}`;
          error++;
        }
      } catch {
        updated[i].status = 'error'; updated[i].message = 'Error de red'; error++;
      }
      setProgress(Math.round(((i + 1) / updated.length) * 100));
      setPreview([...updated]);
      // Pequeña pausa para no saturar la API
      if (i % 10 === 9) await new Promise(r => setTimeout(r, 200));
    }

    setStats({ ok, error, skip });
    setImporting(false);
    setDone(true);
  };

  const pendientesCount = preview.filter(r => r.status === 'pending').length;
  const skipCount = preview.filter(r => r.status === 'skip').length;

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center gap-3 shadow-lg sticky top-0 z-10">
        <button onClick={() => router.push('/admin')} className="text-white text-xl font-bold">&#8592;</button>
        <h1 className="font-bold text-lg">Importar desde Excel</h1>
      </header>

      <main className="p-4 max-w-2xl mx-auto space-y-4 mt-4">
        {/* Tipo de importación */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
          <p className="text-sm font-semibold text-gray-700 mb-3">¿Qué querés importar?</p>
          <div className="grid grid-cols-2 gap-3">
            {(['clientes', 'repuestos'] as ImportType[]).map(t => (
              <button key={t} onClick={() => { setImportType(t); setPreview([]); setFileName(''); setDone(false); }}
                className={`py-3 rounded-xl font-semibold text-sm transition-all ${importType === t ? 'bg-red-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
                {t === 'clientes' ? '🏢 Clientes' : '🔩 Repuestos'}
              </button>
            ))}
          </div>
        </div>

        {/* Columnas esperadas */}
        <div className="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-sm text-blue-800">
          <p className="font-semibold mb-1">Columnas esperadas para {importType}:</p>
          {importType === 'clientes' ? (
            <p className="text-xs">razon_social (o business_name) · email · telefono · cuit · direccion · ciudad</p>
          ) : (
            <p className="text-xs">nombre · codigo (o part_number) · sku · costo · stock · descripcion</p>
          )}
          <p className="text-xs mt-1 text-blue-600">Los nombres de columnas son flexibles — detecta variantes en español e inglés.</p>
        </div>

        {/* Drag & Drop / Selección archivo */}
        <div
          className="bg-white rounded-2xl border-2 border-dashed border-gray-300 p-8 text-center cursor-pointer hover:border-red-400 hover:bg-red-50 transition-all"
          onClick={() => fileRef.current?.click()}
          onDragOver={e => e.preventDefault()}
          onDrop={e => { e.preventDefault(); const f = e.dataTransfer.files[0]; if (f) handleFile(f); }}>
          <p className="text-4xl mb-2">📊</p>
          <p className="font-semibold text-gray-700">{fileName || 'Arrastrá tu archivo Excel aquí'}</p>
          <p className="text-xs text-gray-400 mt-1">O hacé click para seleccionar · .xlsx .xls .csv</p>
          <input ref={fileRef} type="file" accept=".xlsx,.xls,.csv" className="hidden"
            onChange={e => { const f = e.target.files?.[0]; if (f) handleFile(f); }} />
        </div>

        {/* Preview */}
        {preview.length > 0 && (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
              <p className="font-semibold text-gray-800 text-sm">Preview — {preview.length} filas ({pendientesCount} a importar, {skipCount} omitidas)</p>
            </div>
            <div className="overflow-x-auto max-h-64">
              <table className="w-full text-xs">
                <thead className="bg-gray-50 sticky top-0">
                  <tr>
                    <th className="px-3 py-2 text-left text-gray-500 font-medium">Estado</th>
                    {Object.keys(preview[0].data).slice(0, 4).map(h => (
                      <th key={h} className="px-3 py-2 text-left text-gray-500 font-medium">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {preview.slice(0, 50).map((row, i) => (
                    <tr key={i} className={row.status === 'ok' ? 'bg-green-50' : row.status === 'error' ? 'bg-red-50' : row.status === 'skip' ? 'bg-gray-50' : ''}>
                      <td className="px-3 py-1.5">
                        {row.status === 'pending' && <span className="text-gray-400">⏳</span>}
                        {row.status === 'ok' && <span className="text-green-600">✅</span>}
                        {row.status === 'error' && <span className="text-red-600" title={row.message}>❌</span>}
                        {row.status === 'skip' && <span className="text-gray-400" title={row.message}>⏭️</span>}
                      </td>
                      {Object.values(row.data).slice(0, 4).map((v, j) => (
                        <td key={j} className="px-3 py-1.5 text-gray-700 truncate max-w-[120px]">{String(v || '')}</td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
              {preview.length > 50 && <p className="text-xs text-gray-400 text-center py-2">Mostrando 50 de {preview.length} filas</p>}
            </div>
          </div>
        )}

        {/* Progreso */}
        {importing && (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div className="flex items-center justify-between mb-2">
              <p className="text-sm font-semibold text-gray-700">Importando...</p>
              <p className="text-sm text-gray-500">{progress}%</p>
            </div>
            <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
              <div className="h-full bg-red-500 rounded-full transition-all duration-300" style={{ width: `${progress}%` }} />
            </div>
          </div>
        )}

        {/* Resultado */}
        {done && (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p className="font-semibold text-gray-800 mb-3">✅ Importación completada</p>
            <div className="grid grid-cols-3 gap-3 text-center">
              <div className="bg-green-50 rounded-xl p-3"><p className="text-2xl font-bold text-green-700">{stats.ok}</p><p className="text-xs text-green-600">Importados</p></div>
              <div className="bg-red-50 rounded-xl p-3"><p className="text-2xl font-bold text-red-700">{stats.error}</p><p className="text-xs text-red-600">Con error</p></div>
              <div className="bg-gray-50 rounded-xl p-3"><p className="text-2xl font-bold text-gray-500">{stats.skip}</p><p className="text-xs text-gray-400">Omitidos</p></div>
            </div>
          </div>
        )}

        {/* Botón importar */}
        {pendientesCount > 0 && !importing && !done && (
          <button onClick={importar} className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-2xl shadow-lg transition-colors">
            Importar {pendientesCount} {importType} →
          </button>
        )}
        {done && stats.error > 0 && (
          <button onClick={importar} className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-4 rounded-2xl transition-colors">
            Reintentar {stats.error} errores →
          </button>
        )}
      </main>
    </div>
  );
}
