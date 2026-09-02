<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="techstore-booting">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

        <style>
            html.techstore-booting #app,
            body.techstore-booting #app {
                visibility: hidden !important;
            }

            #techstore-loading-screen {
                position: fixed;
                inset: 0;
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
            }

            #techstore-loading-screen .techstore-loading-box {
                display: flex;
                align-items: center;
                flex-direction: column;
                gap: 12px;
            }

            #techstore-loading-screen .techstore-loading-mark {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border: 1px solid #dbeafe;
                border-radius: 12px;
                color: #2563eb;
                background: #eff6ff;
                font-size: 18px;
            }

            #techstore-loading-screen .techstore-loading-spinner {
                width: 20px;
                height: 20px;
                border: 2px solid #dbeafe;
                border-top-color: #2563eb;
                border-radius: 50%;
                animation: techstoreSpin .7s linear infinite;
            }

            #techstore-loading-screen .techstore-loading-text {
                color: #667085;
                font: 700 12px/1.4 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            html:not(.techstore-booting) #techstore-loading-screen,
            body.techstore-page-loading #techstore-loading-screen {
                display: flex;
            }

            html:not(.techstore-booting):not(.techstore-page-loading) #techstore-loading-screen {
                display: none;
            }

            body.techstore-page-loading #app {
                pointer-events: none !important;
            }

            @keyframes techstoreSpin {
                to { transform: rotate(360deg); }
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'TechStore') }}</title>
        </x-inertia::head>
    </head>
    <body class="techstore-booting">
        <x-inertia::app />
        <div id="techstore-loading-screen" aria-hidden="true">
            <div class="techstore-loading-box">
                <div class="techstore-loading-mark"><i class="bi bi-shop-window"></i></div>
                <div class="techstore-loading-spinner"></div>
                <div class="techstore-loading-text">Đang tải TechStore...</div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
