@php
    use Illuminate\Support\Facades\Storage;
    $empresa = \App\Models\Configuracion::actual();
    $logo = $empresa->logo ? Storage::url($empresa->logo) : null;
    $redes = array_filter([
        'Instagram' => $empresa->instagram,
        'Facebook' => $empresa->facebook,
        'TikTok' => $empresa->tiktok,
        'YouTube' => $empresa->youtube,
    ]);
@endphp
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $empresa->nombre }}</title>

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
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $empresa->nombre }}" class="h-10 w-auto">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-marca-600 text-white">{{ \Illuminate\Support\Str::substr($empresa->nombre, 0, 1) }}</span>
                    {{ $empresa->nombre }}
                @endif
            </a>
            @if ($empresa->eslogan)
                <span class="hidden text-sm text-gray-500 sm:block">{{ $empresa->eslogan }}</span>
            @endif
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-gray-100 py-8 text-center text-sm text-gray-500">
        <p class="font-semibold text-gray-700">{{ $empresa->nombre }}</p>
        <p class="mt-1">
            @if ($empresa->telefono)<span>{{ $empresa->telefono }}</span>@endif
            @if ($empresa->telefono && $empresa->email) · @endif
            @if ($empresa->email)<a href="mailto:{{ $empresa->email }}" class="hover:text-marca-700">{{ $empresa->email }}</a>@endif
        </p>
        @if ($empresa->direccion || $empresa->ciudad)
            <p class="mt-1 text-gray-400">{{ collect([$empresa->direccion, $empresa->codigo_postal, $empresa->ciudad])->filter()->join(' · ') }}</p>
        @endif
        @if (count($redes))
            <p class="mt-3 flex flex-wrap items-center justify-center gap-3">
                @foreach ($redes as $nombre => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="font-medium text-marca-600 hover:text-marca-700">{{ $nombre }}</a>
                @endforeach
            </p>
        @endif
        <p class="mt-4 text-xs text-gray-400">© {{ date('Y') }} {{ $empresa->nombre }}@if ($empresa->ciudad) · {{ $empresa->ciudad }}@endif</p>
    </footer>

    @livewireScripts
</body>
</html>
