import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Mining ERP design system: light default, dark via .dark on <html>
    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // surfaces driven by CSS variables (see resources/css/app.css)
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                ink: 'rgb(var(--color-text) / <alpha-value>)',
                muted: 'rgb(var(--color-muted) / <alpha-value>)',
                line: 'rgb(var(--color-border) / <alpha-value>)',
                brand: {
                    50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
                    400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309',
                },
                navy: {
                    700: '#1e293b', 800: '#141c2b', 900: '#0d1420', 950: '#080d16',
                },
            },
            borderRadius: {
                card: '0.75rem', // 12px — single consistent card radius
                ctl: '0.5rem',   // 8px — inputs & buttons
            },
            boxShadow: {
                card: '0 1px 2px rgb(15 23 42 / 0.05)',
                pop: '0 8px 24px rgb(15 23 42 / 0.12)',
            },
            keyframes: {
                shimmer: { '100%': { transform: 'translateX(100%)' } },
                fadeup: { from: { opacity: '0', transform: 'translateY(4px)' }, to: { opacity: '1', transform: 'none' } },
            },
            animation: {
                fadeup: 'fadeup .18s ease-out',
            },
        },
    },

    plugins: [forms],
};
