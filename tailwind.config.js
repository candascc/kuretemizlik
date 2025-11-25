/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/Views/**/*.php",
    "./src/Views/layout/**/*.php",
    "./src/Views/portal/**/*.php",
    "./src/Views/resident/**/*.php",
    "./src/Views/errors/**/*.php",
    "./src/Views/tools/**/*.php",
    "./src/Views/auth/**/*.php",
    "./src/Views/admin/**/*.php",
    "./portal/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      // Existing design colors and styles are preserved
      // Only extend if necessary, don't override defaults
    },
  },
  plugins: [],
}

