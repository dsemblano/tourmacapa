/** @type {import('tailwindcss').Config} config */
const config = {
  content: ['./app/**/*.php', './resources/**/*.{php,vue,js}'],
  theme: {
    container: {
      padding: {
      DEFAULT: '1rem',
      sm: '2rem',
      lg: '4rem',
      xl: '5rem',
    },
    fontFamily: {
      'ibmplexsans': ['ibmplexsans', 'sans-serif']
    },
      center: true,
    },
    extend: {
      colors: {
        primaryColor: '#ff174d',
        blueBg: '#f7f9ff',
        grayH: '#333'
      }, // Extend Tailwind's default colors
    },
  },
  plugins: [],
};

export default config;
