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
    buildExcludes: [/middleware-manifest\.json$/],
    runtimeCaching: [
      {
        urlPattern: /^https:\/\/sertecapp\.pendziuch\.com\/api\/.*/i,
        handler: 'NetworkFirst',
        options: {
          cacheName: 'api-cache-v2',
          expiration: { maxEntries: 200, maxAgeSeconds: 86400 },
          networkTimeoutSeconds: 10,
        },
      },
      {
        urlPattern: /^https:\/\/sertecapp-tecnicos\.pages\.dev\/.*/i,
        handler: 'StaleWhileRevalidate',
        options: {
          cacheName: 'pages-cache-v2',
          expiration: { maxEntries: 50, maxAgeSeconds: 3600 },
        },
      },
    ],
  });
  module.exports = withPWA({ output: 'export', trailingSlash: true });
} else {
  module.exports = nextConfig;
}
