'use client';

import { Suspense } from 'react';
import AutoLoginContent from './AutoLoginContent';

export default function AutoLoginPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin h-12 w-12 border-4 border-white border-t-transparent rounded-full mx-auto mb-4"></div>
          <p className="text-white text-lg">Cargando...</p>
        </div>
      </div>
    }>
      <AutoLoginContent />
    </Suspense>
  );
}
