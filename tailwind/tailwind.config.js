/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/views/**/*.php',
    './app/core/**/*.php',
    './public_html/**/*.php',
    './public_html/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      screens: {
        'nav': '1100px',
      },
      colors: {
        primary: {
          50: '#eef1fc',
          100: '#dbe2f8',
          200: '#b9c7f1',
          300: '#94a7e9',
          400: '#5c76db',
          500: '#2F3A8F', // Brand Navy
          600: '#28317c',
          700: '#202664',
          800: '#191d4d',
          900: '#14173e',
          DEFAULT: '#2F3A8F',
        },
        accent: {
          50: '#f2f8f2',
          100: '#e1efe1',
          200: '#c5dec5',
          300: '#9ec49f',
          400: '#69a76b',
          500: '#3B8E3F', // Brand Green
          600: '#317935',
          700: '#28622c',
          800: '#214e24',
          900: '#1a3e1d',
          DEFAULT: '#3B8E3F',
        },
        surface: {
          DEFAULT: '#FAFAF7', // Warm Eco-White background
          alt: '#F4F4EE',
          card: '#FFFFFF',
        },
        ink: {
          DEFAULT: '#1A1D29', // Main text ink
          soft: '#2D3345',
          muted: '#5F677D',
          subtle: '#8C95AA',
        },
        border: {
          subtle: '#E8E8E2',
          DEFAULT: '#DFE2E6',
          strong: '#CBD0D7',
        },
      },
      fontFamily: {
        serif: ['Fraunces', 'Georgia', 'serif'],
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      maxWidth: {
        prose: '68ch',
      },
      borderRadius: {
        'xl': '0.75rem',
        '2xl': '1rem',
      },
      boxShadow: {
        'soft-sm': '0 1px 3px 0 rgba(26, 29, 41, 0.04), 0 1px 2px -1px rgba(26, 29, 41, 0.04)',
        'soft': '0 4px 12px -2px rgba(26, 29, 41, 0.05), 0 2px 6px -2px rgba(26, 29, 41, 0.03)',
        'soft-lg': '0 10px 24px -4px rgba(26, 29, 41, 0.06), 0 6px 12px -4px rgba(26, 29, 41, 0.03)',
      },
    },
  },
  plugins: [],
};
