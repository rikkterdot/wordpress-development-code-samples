const mix = require('laravel-mix');

mix.setPublicPath('public');

mix.js('assets/js/main.js', 'public/js')
   .sass('assets/css/style.scss', 'public/css')
   .options({
       processCssUrls: false
   })
   .sourceMaps();

// Enable versioning in production
if (mix.inProduction()) {
    mix.version();
}
