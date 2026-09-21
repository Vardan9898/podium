<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="h-full font-sans antialiased">
    <div id="app" class="min-h-full"></div>
</body>
</html>
