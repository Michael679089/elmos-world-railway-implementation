<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <title>Think Finance</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
            rel="stylesheet"
        />
        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) 
            @vite(['resources/css/app.css', 'resources/js/app.js']) 
        @else 
        
        @endif
    </head>
    <body class="bg-yellow-10">
        
        {{-- Adjust background color to match design --}}
        @include('components.header')

        <main class="container mx-auto px-4 pb-10">
            @include('layouts.flash-messages')
            @yield('content')
        </main>

        @include('components.footer')
    </body>
</html>
