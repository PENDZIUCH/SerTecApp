'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../lib/config';

function requestGeoPermission() {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(() => {}, () => {}, { timeout: 5000, maximumAge: 0 });
}

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [pin, setPin] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [savedUser, setSavedUser] = useState<any>(null);
  const [checking, setChecking] = useState(true);
  const [showPass, setShowPass] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    if (token && user) {
      try {
        setSavedUser(JSON.parse(user));
      } catch {}
    }
    setChecking(false);
  }, []);

  const enterWithSavedSession = () => {
    const roles: string[] = savedUser?.roles || [];
    const isAdmin = roles.includes('administrador') || roles.includes('admin');
    router.push(isAdmin ? '/admin' : '/ordenes');
  };

  const clearSession = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setSavedUser(null);
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const response = await fetch(`${API_URL}/api/v1/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, password: pin }),
      });
      const data = await response.json();
      if (response.ok && data.token) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        requestGeoPermission();
        const roles: string[] = data.user?.roles || [];
        const isAdmin = roles.includes('administrador') || roles.includes('admin');
        setTimeout(() => router.push(isAdmin ? '/admin' : '/ordenes'), 300);
      } else {
        setError(data.message || 'Credenciales incorrectas');
      }
    } catch (err) {
      const sv = localStorage.getItem('user');
      const st = localStorage.getItem('token');
      if (sv && st) {
        const user = JSON.parse(sv);
        if (user.email === email) {
          setError('Sin conexión. Entrando con datos guardados...');
          const roles: string[] = user?.roles || [];
          const isAdmin = roles.includes('administrador') || roles.includes('admin');
          setTimeout(() => router.push(isAdmin ? '/admin' : '/ordenes'), 1000);
          return;
        }
      }
      setError('Error de conexión. Verificá tu internet.');
    } finally {
      setLoading(false);
    }
  };

  if (checking) {
    return <div className="min-h-screen bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
      <div className="text-white text-lg">Cargando...</div>
    </div>;
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
            <img src="/icon.svg" alt="Fitness Company" className="w-16 h-16" />
          </div>
          <h1 className="text-3xl font-bold text-white mb-2">Fitness Company</h1>
          <p className="text-red-100">Portal SerTecApp</p>
        </div>

        <div className="bg-white rounded-3xl shadow-2xl p-8">
          {savedUser ? (
            <div className="space-y-6 text-center">
              <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <span className="text-2xl font-bold text-red-600">
                  {savedUser.name?.charAt(0)?.toUpperCase()}
                </span>
              </div>
              <div>
                <p className="text-gray-500 text-sm mb-1">Bienvenido de vuelta</p>
                <h2 className="text-2xl font-bold text-gray-900">{savedUser.name}</h2>
                <p className="text-gray-400 text-sm mt-1">{savedUser.email}</p>
              </div>
              <button onClick={enterWithSavedSession}
                className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl transition-all shadow-lg">
                ENTRAR
              </button>
              <button onClick={clearSession}
                className="w-full text-gray-400 hover:text-gray-600 text-sm py-2 transition-colors">
                No soy {savedUser.name?.split(' ')[0]} — cambiar usuario
              </button>
            </div>
          ) : (
            <form onSubmit={handleLogin} className="space-y-6">
              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all text-gray-900"
                  placeholder="usuario@demo.com" />
              </div>
              <div>
                <label htmlFor="pin" className="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                <div className="relative">
                  <input id="pin" type={showPass ? 'text' : 'password'} value={pin} onChange={(e) => setPin(e.target.value)} required
                    className="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all text-gray-900"
                    placeholder="••••••••" />
                  <button type="button" onClick={() => setShowPass(!showPass)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                    {showPass ? (
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    ) : (
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    )}
                  </button>
                </div>
              </div>
              {error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
              )}
              <button type="submit" disabled={loading}
                className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl transition-all shadow-lg disabled:opacity-50">
                {loading ? 'Ingresando...' : 'INGRESAR'}
              </button>
            </form>
          )}
        </div>
        <p className="text-center text-red-100 text-sm mt-6">v2.0.0 - Fitness Company © 2026</p>
      </div>
    </div>
  );
}