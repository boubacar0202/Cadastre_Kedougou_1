import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import scrollbar from 'tailwind-scrollbar';


/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

     theme: {
        extend: {
            colors: {
                "primary": '#613104', // Définir primary comme  #5f2e01
                "primary-dark": "#A35200", //   #A35200
                "primary-txt": "#66380a",   // #592D00
                "primary-only": "#E6C5A2", // #E6C5A2
                "primary-menu": "#C7812E", //  #C7812E
                "primary-form": "#ffe5c5", //  #ffe5c5
                "primary-layout": "#faefe6ff", //  #006afd            },
            // Ajoute la personnalisation de la scrollbar
            scrollbar: ['rounded'],
        },
    },

    plugins: [
        require('tailwind-scrollbar')
    ],
    }
};