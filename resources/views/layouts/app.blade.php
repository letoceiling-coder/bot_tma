<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.1/css/all.css">
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @seo
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <script>
        let settingsSite = @json($settings);
    </script>
</head>
<body>
<div id="app">

</div>
</body>
</html>
