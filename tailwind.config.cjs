/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.ts',
    './resources/**/*.jsx',
    './resources/**/*.tsx',
  ],
  theme: {
    extend: {
      colors: {
        'role-client': 'var(--role-client)',
        'role-dcd': 'var(--role-dcd)',
        'role-da': 'var(--role-da)'
      }
    }
  },
  plugins: [],
};
