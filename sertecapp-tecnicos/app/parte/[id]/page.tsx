import PartePageClient from './_client';

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: 'placeholder' }];
}

export default function PartePage() {
  return <PartePageClient />;
}
