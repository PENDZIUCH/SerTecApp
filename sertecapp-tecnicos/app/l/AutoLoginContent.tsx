'use client';

import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function AutoLoginContent() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const token = searchParams.get('t');
    
    if (!token) {
      router.push('/');
      return;
    }

    try {
      // Decodear Base64
      const decoded = atob(token);
      const [email, password] = decoded.split(':');

      if (!email || !password) {
        alert('Link inválido');
        router.push('/');
        return;
      }

      // Login automático
      localStorage.setItem('token', 'demo-token-123');
      localStorage.setItem('user', JSON.stringify({ 
        id: 4, 
        name: email.split('@')[0],
        email: email 
      }));
      
      // Redirect a órdenes
      router.push('/ordenes');
    } catch (error) {
      console.error('Error decodificando token:', error);
      alert('Link inválido o corrupto');
      router.push('/');
    }
  }, [searchParams, router]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin h-12 w-12 border-4 border-white border-t-transparent rounded-full mx-auto mb-4"></div>
        <p className="text-white text-lg">Ingresando...</p>
      </div>
    </div>
  );
}
