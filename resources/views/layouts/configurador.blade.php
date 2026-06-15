<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- DECISIÓN: Tailwind y flatpickr vía CDN para que el configurador sea autónomo y
         desplegable en Dinahosting sin paso de build (npm). Livewire ya incluye Alpine. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        marca: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e',
                            800: '#115e59', 900: '#134e4a',
                        },
                        acento: {
                            100: '#ffe4e6', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>

    @livewireStyles
</head>
<body class="min-h-screen bg-gradient-to-b from-marca-50 to-white font-sans text-gray-800 antialiased">
    <header class="border-b border-marca-100 bg-white/70 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-marca-700">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-marca-600 text-white">T</span>
                Troula Eventos
            </a>
            <span class="hidden text-sm text-gray-500 sm:block">Fotomatones y experiencias para tu evento</span>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-gray-100 py-6 text-center text-sm text-gray-400">
        © {{ date('Y') }} Troula Eventos · A Coruña
    </footer>

    @livewireScripts
</body>
</html>
