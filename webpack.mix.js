let mix = require('laravel-mix');
mix.setPublicPath('./');
mix.js('public/src/js/wpdrift-worker-public.js', 'public/js');
