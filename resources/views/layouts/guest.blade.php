{{--
    Guest Layout — CatVRF
    Minimal centered layout for auth pages (login, register, password reset, 2FA)
    Mobile-first, dark glassmorphic, no navigation
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full" x-data>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="correlation-id" content="{{ request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()) }}">
    <meta name="theme-color" content="#0a0a0f">

    <title>{{ config('app.name', 'CatVRF') }}{{ isset($pageTitle) ? ' — ' . $pageTitle : '' }}</title>

    {{-- Preload fonts --}}
    <link rel="preload" href="/fonts/satoshi-variable.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-full bg-carbon-950 text-white antialiased overflow-x-hidden">

    {{-- Ambient background --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute -top-48 -left-48 w-96 h-96 bg-neuro-indigo-600/10 rounded-full blur-[128px]"></div>
        <div class="absolute top-1/2 -right-48 w-80 h-80 bg-organic-teal-500/8 rounded-full blur-[96px]"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-48 bg-gradient-to-t from-neuro-indigo-900/20 to-transparent"></div>
    </div>

    {{-- Main wrapper --}}
    <main
        class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12"
        id="main-content"
    >

        {{-- Logo --}}
        <a
            href="{{ url('/') }}"
            class="mb-8 flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary rounded-xl"
            aria-label="{{ config('app.name', 'CatVRF') }} — вернуться на главную"
        >
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-neuro-indigo-500 to-organic-teal-500 flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.4)]">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-white group-hover:text-neuro-indigo-300 transition-colors">
                {{ config('app.name', 'CatVRF') }}
            </span>
        </a>

        {{-- Auth card --}}
        <div class="w-full max-w-md">
            <div class="bg-black/30 backdrop-blur-xl border border-white/8 rounded-3xl shadow-modal overflow-hidden">

                {{-- Optional page heading --}}
                @if(isset($heading))
                    <div class="px-6 pt-6 pb-4 border-b border-white/5">
                        <h1 class="text-lg font-semibold text-white">{{ $heading }}</h1>
                        @if(isset($subheading))
                            <p class="mt-1 text-xs text-carbon-400">{{ $subheading }}</p>
                        @endif
                    </div>
                @endif

                {{-- Page content --}}
                <div class="p-6">
                    {{ $slot }}
                </div>

                {{-- Optional footer --}}
                @if(isset($footer))
                    <div class="px-6 pb-6">
                        <div class="h-px bg-white/5 mb-4"></div>
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Back link --}}
        <p class="mt-6 text-xs text-carbon-500">
            <a href="{{ url('/') }}" class="hover:text-white transition-colors focus:outline-none focus-visible:underline">
                ← На главную
            </a>
        </p>

    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
