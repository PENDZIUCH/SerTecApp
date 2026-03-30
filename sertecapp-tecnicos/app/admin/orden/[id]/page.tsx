import OrdenEditClient from './_client';

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: 'placeholder' }];
}

export default function OrdenEditPage() {
  return <OrdenEditClient />;
}
