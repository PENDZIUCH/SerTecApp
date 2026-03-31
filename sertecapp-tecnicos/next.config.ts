/** @type {import('next').NextConfig} */

const isProd = process.env.NEXT_EXPORT === '1';

let nextConfig = {};

if (isProd) {
  const withPWA = require('next-pwa')({
    dest: 'public',
    register: true,
    skipWaiting: true,
    disable: false,
    fallbacks: { document: '/offline.html' },
    runtimeCaching: [
      {
        urlPattern: /^https:\/\/sertecapp\.pendziuch\.com\/api\/.*/i,
        handler: 'NetworkFirst',
        options: { cacheName: 'api-cache', expiration: { maxEntries: 200, maxAgeSeconds: 86400 }, networkTimeoutSeconds: 10 },
      },
    ],
  });
  module.exports = withPWA({ output: 'export', trailingSlash: true });
} else {
  module.exports = nextConfig;
}
