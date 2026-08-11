{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Aksara') }}</title>
        @php
            $brandAssets = \App\Support\BrandAttribution::assetUrls();
        @endphp
        <link rel="icon" href="{{ $brandAssets['faviconUrl'] }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ $brandAssets['faviconUrl'] }}" type="image/x-icon">
        <link rel="apple-touch-icon" href="{{ $brandAssets['appleTouchUrl'] }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=literata:500,600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Anti-FOUC sidebar widths (same as legacy layout) --}}
        <script>
            (function () {
                var c = localStorage.getItem('aksara_sidebar_collapsed') === 'true';
                document.documentElement.classList.toggle('sidebar-collapsed', c);
            })();
        </script>
        <style>
            .aksara-sidebar-rail { width: 16rem; }
            .aksara-content-rail { padding-left: 16rem; }
            .sidebar-collapsed .aksara-sidebar-rail { width: 5rem; }
            .sidebar-collapsed .aksara-content-rail { padding-left: 5rem; }
            .aksara-sidebar-edge-toggle {
                left: 16rem;
            }
            .sidebar-collapsed .aksara-sidebar-edge-toggle {
                left: 5rem;
            }
            .sidebar-animate .aksara-sidebar-rail,
            .sidebar-animate .aksara-content-rail {
                transition: width 300ms ease, padding-left 300ms ease;
            }
            .sidebar-animate .aksara-sidebar-edge-toggle {
                transition: left 300ms ease;
            }
            @media (max-width: 1023px) {
                .aksara-sidebar-rail { width: 0 !important; }
                .aksara-content-rail { padding-left: 0 !important; }
                .aksara-sidebar-edge-toggle { display: none !important; }
            }
            .sidebar-collapsed .aksara-sidebar-rail { overflow: visible !important; }
        </style>

        @vite(['resources/css/app.css', 'resources/js/inertia-app.js'])
        @inertiaHead
    </head>
    <body class="font-sans aksara-shell antialiased">
        @inertia
    </body>
</html>
