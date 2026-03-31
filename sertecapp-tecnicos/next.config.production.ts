const withPWA = require("next-pwa")({
  dest: "public",
  register: true,
  skipWaiting: true,
  disable: false,
  fallbacks: {
    document: "/offline.html",
  },
});

const nextConfig = {
  output: 'export',
  trailingSlash: true,
  webpack: (config) => config,
};

module.exports = withPWA(nextConfig);
