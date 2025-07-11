const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   // .sass('resources/sass/403.scss', 'public/css')
   .browserSync({
      proxy: 'botacura.com',
      socket: {
         clients: false, // Desactiva el WebSocket de BrowserSync
      },
      files: [
         'app/**/*.php',
         'resources/views/**/*.php',
         'public/js/**/*.js',
         'public/css/**/*.css',
      ]
  });
  
  // .browserSync({
  //    proxy: 'botacura.com',
     
  //    files: [
  //       'app/**/*.php',
  //       'resources/views/**/*.php',
  //       'public/js/**/*.js',
  //       'public/css/**/*.css',
  //    ]
  // });