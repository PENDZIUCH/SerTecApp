'use client';

import { Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import OrdenEditClient from './[id]/_client';

function OrdenWrapper() {
  const searchParams = useSearchParams();
  const id = searchParams.get('id');
  if (!id) {
    const router = useRouter();
    router.push('/admin');
    return null;
  }
  return <OrdenEditClient />;
}

export default function OrdenPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full"></div>
      </div>
    }>
      <OrdenWrapper />
    </Suspense>
  );
}
