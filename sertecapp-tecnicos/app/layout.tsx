import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "SerTecApp - Técnicos",
  description: "Aplicación para técnicos de Fitness Company",
  manifest: "/manifest.json",
  themeColor: "#dc2626",
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "SerTecApp",
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
