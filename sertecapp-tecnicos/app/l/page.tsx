'use client';

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function MagicLinkPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const token = searchParams.get('t');

    if (!token) {
      router.push('/');
      return;
    }

    // Verificar token con el backend
    const verifyToken = async () => {
      try {
        const apiUrl = 'https://sertecapp.pendziuch.com';
        const response = await fetch(`${apiUrl}/api/v1/magic-link/verify?token=${token}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          
          // Guardar token y user
          localStorage.setItem('token', token);
          localStorage.setItem('user', JSON.stringify(data.user));
          
          // Redirigir a órdenes
          router.push('/ordenes');
        } else {
          // Token inválido o expirado
          router.push('/?error=invalid_token');
        }
      } catch (error) {
        console.error('Error verifying token:', error);
        router.push('/?error=connection');
      }
    };

    verifyToken();
  }, [searchParams, router]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
      <div className="text-center">
        <div className="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
          <img src="/icon.svg" alt="Fitness Company" className="w-16 h-16" />
        </div>
        <h1 className="text-2xl font-bold text-white mb-2">Verificando acceso...</h1>
        <div className="animate-spin h-8 w-8 border-4 border-white border-t-transparent rounded-full mx-auto"></div>
      </div>
    </div>
  );
}
