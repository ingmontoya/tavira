/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./components/**/*.{js,vue,ts}",
    "./layouts/**/*.vue",
    "./pages/**/*.vue",
    "./plugins/**/*.{js,ts}",
    "./app.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#c64832',
          50: '#fef2f0',
          100: '#fde5e1',
          200: '#fbcfc7',
          300: '#f7ada0',
          400: '#f17d68',
          500: '#e45a3e',
          600: '#c64832',
          700: '#a63620',
          800: '#88301e',
          900: '#702c1e',
        },
        secondary: {
          DEFAULT: '#3867aa',
          50: '#f0f5fc',
          100: '#e1ebf9',
          200: '#c9dbf5',
          300: '#a4c3ee',
          400: '#79a2e5',
          500: '#5883dc',
          600: '#3867aa',
          700: '#3459a4',
          800: '#2f4b86',
          900: '#2a406b',
        },
        accent: {
          DEFAULT: '#002e82',
          50: '#e6edf9',
          100: '#ccdcf4',
          200: '#99b9e8',
          300: '#6696dd',
          400: '#3373d1',
          500: '#0050c6',
          600: '#002e82',
          700: '#002562',
          800: '#001c41',
          900: '#001221',
        }
      },
      fontFamily: {
        brand: ['Poppins', 'sans-serif'],
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
