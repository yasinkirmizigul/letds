/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
        "./resources/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                border: 'hsl(var(--border) / <alpha-value>)',
                background: 'hsl(var(--background) / <alpha-value>)',
                foreground: 'hsl(var(--foreground) / <alpha-value>)',
                muted: { foreground: 'hsl(var(--muted-foreground) / <alpha-value>)' },
                secondary: { foreground: 'hsl(var(--secondary-foreground) / <alpha-value>)' },
            },
            spacing: {
                '5.5': '1.375rem',
                '7.5': '1.875rem',
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
