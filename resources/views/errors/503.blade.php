<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Service Unavailable | {{ config('app.name', 'Bindamy Mareas') }}</title>

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme --}}
    <style>
        html {
            background-color: hsl(0 0% 100%);
        }

        html.dark {
            background-color: hsl(0 0% 3.9%);
        }
    </style>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: hsl(0 0% 100%);
            color: hsl(0 0% 3.9%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        html.dark body {
            background: hsl(0 0% 3.9%);
            color: hsl(0 0% 98%);
        }

        .error-container {
            max-width: 42rem;
            width: 100%;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
        }

        .logo-container img {
            height: 2rem;
            width: auto;
        }

        .error-code {
            font-size: 6rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(to right, hsl(45 90% 50%), hsl(45 90% 55%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        html.dark .error-code {
            background: linear-gradient(to right, hsl(45 90% 55%), hsl(45 90% 60%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-title {
            font-size: 1.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: hsl(0 0% 3.9%);
        }

        html.dark .error-title {
            color: hsl(0 0% 98%);
        }

        .error-description {
            font-size: 1rem;
            color: hsl(0 0% 45.1%);
            margin-bottom: 2rem;
        }

        html.dark .error-description {
            color: hsl(0 0% 63.9%);
        }

        .error-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            height: 2.5rem;
            padding: 0 1rem;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: hsl(217 91% 60%);
            color: hsl(0 0% 98%);
        }

        .btn-primary:hover {
            background: hsl(217 91% 55%);
        }

        html.dark .btn-primary {
            background: hsl(217 91% 70%);
            color: hsl(0 0% 9%);
        }

        html.dark .btn-primary:hover {
            background: hsl(217 91% 75%);
        }

        .btn-secondary {
            background: transparent;
            border-color: hsl(0 0% 92.8%);
            color: hsl(0 0% 3.9%);
        }

        .btn-secondary:hover {
            background: hsl(0 0% 96.1%);
        }

        html.dark .btn-secondary {
            border-color: hsl(0 0% 14.9%);
            color: hsl(0 0% 98%);
        }

        html.dark .btn-secondary:hover {
            background: hsl(0 0% 16.08%);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo-container">
            <img
                src="{{ asset('bindamy-marea-logo-light.svg') }}"
                alt="Bindamy Mareas"
                class="light-logo"
            />
            <img
                src="{{ asset('bindamy-marea-logo-dark.svg') }}"
                alt="Bindamy Mareas"
                class="dark-logo"
                style="display: none;"
            />
        </div>

        <div class="error-code">503</div>
        <h1 class="error-title">{{ __('errors.Service Unavailable') }}</h1>
        <p class="error-description">
            {{ __('errors.We\'re currently performing maintenance. Please check back soon.') }}
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">{{ __('errors.Go Home') }}</a>
            <button onclick="location.reload()" class="btn btn-secondary">{{ __('errors.Refresh') }}</button>
        </div>
    </div>

    <script>
        // Toggle logo based on theme
        function updateLogo() {
            const isDark = document.documentElement.classList.contains('dark');
            const lightLogo = document.querySelector('.light-logo');
            const darkLogo = document.querySelector('.dark-logo');

            if (isDark) {
                if (lightLogo) lightLogo.style.display = 'none';
                if (darkLogo) darkLogo.style.display = 'block';
            } else {
                if (lightLogo) lightLogo.style.display = 'block';
                if (darkLogo) darkLogo.style.display = 'none';
            }
        }

        // Check theme on load
        updateLogo();

        // Watch for theme changes
        const observer = new MutationObserver(() => {
            updateLogo();
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Watch system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', () => {
            if (!document.documentElement.classList.contains('dark') && !document.documentElement.classList.contains('light')) {
                updateLogo();
            }
        });
    </script>
</body>
</html>
