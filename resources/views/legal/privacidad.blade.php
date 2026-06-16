@php
    use Illuminate\Support\Facades\Storage;
    $empresa = \App\Models\Configuracion::actual();
    $logo = $empresa->logo ? Storage::url($empresa->logo) : null;
@endphp
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de privacidad · {{ $empresa->nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { marca: {
            50: '#f0fdfa', 100: '#ccfbf1', 600: '#0d9488', 700: '#0f766e',
        } } } } };
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>
        .contenido-legal { line-height: 1.7; }
        .contenido-legal h2 { font-size: 1.5rem; font-weight: 700; margin: 1.5rem 0 .75rem; color: #111827; }
        .contenido-legal h3 { font-size: 1.15rem; font-weight: 600; margin: 1.25rem 0 .5rem; color: #1f2937; }
        .contenido-legal p { margin: .75rem 0; color: #374151; }
        .contenido-legal ul { list-style: disc; padding-left: 1.5rem; margin: .75rem 0; color: #374151; }
        .contenido-legal ol { list-style: decimal; padding-left: 1.5rem; margin: .75rem 0; color: #374151; }
        .contenido-legal a { color: #0f766e; text-decoration: underline; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-marca-50 to-white" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
    <header class="border-b border-marca-100 bg-white/70 backdrop-blur">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-marca-700">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $empresa->nombre }}" class="h-10 w-auto">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-marca-600 text-white">{{ \Illuminate\Support\Str::substr($empresa->nombre, 0, 1) }}</span>
                    {{ $empresa->nombre }}
                @endif
            </a>
            <a href="/" class="text-sm font-medium text-marca-700 hover:underline">← Volver</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10">
        <div class="rounded-3xl border border-gray-100 bg-white p-8 shadow-sm">
            @if (filled($empresa->politica_privacidad))
                <div class="contenido-legal">{!! $empresa->politica_privacidad !!}</div>
            @else
                <h1 class="text-2xl font-bold text-gray-900">Política de privacidad</h1>
                <p class="mt-3 text-gray-500">Esta página aún no tiene contenido. El administrador puede redactarla desde el panel.</p>
            @endif
        </div>
    </main>

    <footer class="py-8 text-center text-sm text-gray-400">
        © {{ date('Y') }} {{ $empresa->nombre }}@if ($empresa->ciudad) · {{ $empresa->ciudad }}@endif
    </footer>
</body>
</html>
