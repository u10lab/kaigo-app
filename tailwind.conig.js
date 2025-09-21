/** @type {import('tailwindcss').Config} */
export default {
    content: [
      "./index.html",
      "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
      extend: {
        colors: {
          'primary-green': '#10B981',
          'primary-blue': '#3B82F6',
          'primary-red': '#EF4444',
          'primary-yellow': '#F59E0B',
          'text-primary': '#1F2937',
          'text-secondary': '#6B7280',
          'bg-primary': '#FFFFFF',
          'bg-secondary': '#F9FAFB',
        },
        minHeight: {
          'touch': '44px',
        },
        minWidth: {
          'touch': '44px',
        }
      },
    },
    plugins: [
      require('@tailwindcss/forms'),
    ],
  }