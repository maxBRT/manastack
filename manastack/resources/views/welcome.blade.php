<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <header class="flex items-center justify-between p-6">
            <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ config('app.name') }}</span>

            @if (Route::has('login'))
                <nav class="flex items-center gap-2">
                    @auth
                        <flux:button href="{{ url('/dashboard') }}">Dashboard</flux:button>
                    @else
                        <flux:button href="{{ route('login') }}" variant="ghost">Log in</flux:button>

                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" variant="primary">Register</flux:button>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <main class="p-6">
        </main>

        @fluxScripts
    </body>
</html>
