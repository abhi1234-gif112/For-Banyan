/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        navy:      '#1B2A4A',
        gold:      '#C9A84C',
        positive:  '#1D7A4A',
        negative:  '#C0392B',
        watchlist: '#B8860B',
        neutral:   '#5F5E5A',
        bg:        '#F4F6FA',
        border:    '#E2E8F0',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
