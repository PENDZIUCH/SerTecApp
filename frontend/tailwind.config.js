/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', // Esto es crucial para que funcione el dark mode con clases
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
