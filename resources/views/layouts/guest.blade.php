<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LeadFlow') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-brand-blue/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-96 h-96 bg-brand-cyan/5 rounded-full blur-3xl"></div>

            <div class="z-10 w-full sm:max-w-md px-6 py-12 bg-white shadow-2xl shadow-slate-200/50 sm:rounded-3xl border border-slate-100">
                <div class="flex flex-col items-center mb-8">
                    <div class="w-16 h-16 bg-navy-900 rounded-2xl flex items-center justify-center shadow-xl mb-4">
                        <svg class="w-10 h-10 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">eGSales AI</h1>
                    <p class="text-slate-400 text-sm mt-1">LeadFlow Intelligence Portal</p>
                </div>

                {{ $slot }}
            </div>
            
            <div class="mt-8 text-slate-400 text-xs font-medium z-10">
                &copy; {{ date('Y') }} eGSales AI. All rights reserved.
            </div>
        </div>
    </body>
</html>
