import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "Fitness Company - Técnicos",
  description: "Aplicación para técnicos de Fitness Company",
  manifest: "/manifest.json",
  themeColor: "#dc2626",
  icons: {
    icon: '/icon.svg',
  },
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "Fitness Company",
  },
  openGraph: {
    title: "SerTecApp - Técnicos",
    description: "Aplicación para técnicos de Fitness Company",
    url: "https://pro.pendziuch.com",
    siteName: "SerTecApp",
    images: [
      {
        url: '/icon.svg',
        width: 512,
        height: 512,
        alt: 'SerTecApp Logo',
      },
    ],
    locale: 'es_AR',
    type: 'website',
  },
  twitter: {
    card: 'summary',
    title: "SerTecApp - Técnicos",
    description: "Aplicación para técnicos de Fitness Company",
    images: ['/icon.svg'],
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="es">
      <head>
        <link rel="icon" href="/icon.svg" type="image/svg+xml" />
        <link rel="manifest" href="/manifest.json" />
        <meta name="theme-color" content="#dc2626" />
        <link rel="apple-touch-icon" href="/icon.svg" />
      </head>
      <body className={`${inter.className} antialiased`}>
        {children}
        <script dangerouslySetInnerHTML={{
          __html: `
            if ('serviceWorker' in navigator) {
              window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(
                  (registration) => console.log('SW registrado:', registration.scope),
                  (error) => console.error('SW falló:', error)
                );
              });
            }
          `
        }} />
      </body>
    </html>
  );
}
