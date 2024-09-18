/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx", 
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        "soft-green": "#F7FFF4",
        "green": "#C1FDBB",
        "light-green": "#209B1E",
      },
    },
  },
  plugins: [],
}

