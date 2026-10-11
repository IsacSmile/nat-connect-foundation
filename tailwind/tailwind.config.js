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
        primary: '#2F3A8F', // Brand Navy
        'primary-hover': '#232C70',
        accent: '#3B8E3F',  // Decorative Accent Green (decorative only)
        'accent-strong': '#2E7D32', // Passes WCAG AA (~5.1:1 against white) for text and solid button bg
        'accent-hover': '#1B5E20',
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
