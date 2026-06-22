<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>eGSales AI - Secure Access</title>

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,600,800" rel="stylesheet" />

    <style>
        :root {
            --brand-blue: #4358C3;
            --brand-cyan: #1A98C8;
            --bg-pure-black: #010409;
        }

        body {
            background-color: var(--bg-pure-black);
            background-image: radial-gradient(circle at center, #0f172a 0%, #000 100%);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- CONCENTRATED ORBITAL ANIMATION --- */
        .automation-bg {
            position: absolute;
            width: 600px;
            /* Limited area */
            height: 600px;
            z-index: 1;
            /* Sits right behind the card */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .orb-container {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .orb-circle {
            fill: none;
            stroke: var(--brand-blue);
            stroke-width: 1.2;
            transform-origin: center;
            /* Stronger neon glow for visibility */
            filter: drop-shadow(0 0 12px rgba(67, 88, 195, 0.7));
            animation: rotate-orb var(--duration) linear infinite;
        }

        @keyframes rotate-orb {
            0% {
                transform: rotate(0deg) scale(0.9);
                opacity: 0.3;
            }

            50% {
                transform: rotate(180deg) scale(1.05);
                opacity: 0.8;
            }

            100% {
                transform: rotate(360deg) scale(0.9);
                opacity: 0.3;
            }
        }

        /* --- CARD DESIGN --- */
        .ai-card {
            background: rgba(13, 17, 23, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(26, 152, 200, 0.3);
            position: relative;
            z-index: 10;
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.9), inset 0 0 15px rgba(255, 255, 255, 0.05);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-field {
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .input-field:focus {
            border-color: var(--brand-cyan) !important;
            box-shadow: 0 0 15px rgba(26, 152, 200, 0.2);
            outline: none;
        }

        .btn-glow {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-cyan));
            box-shadow: 0 5px 20px rgba(26, 152, 200, 0.4);
            transition: all 0.3s ease;
        }

        .btn-glow:active {
            transform: scale(0.98);
        }

        .error-field {
            border-color: rgba(239, 68, 68, 0.6) !important;
        }
    </style>
</head>

<body>

    <div class="automation-bg">
        <svg class="orb-container" viewBox="0 0 200 200">
            <ellipse class="orb-circle" cx="100" cy="100" rx="90" ry="30" style="--duration: 22s;" />
            <ellipse class="orb-circle" cx="100" cy="100" rx="80" ry="50" style="--duration: 16s;" />
            <ellipse class="orb-circle" cx="100" cy="100" rx="60" ry="85" style="--duration: 20s;" />
            <ellipse class="orb-circle" cx="100" cy="100" rx="40" ry="95" style="--duration: 14s;" />
        </svg>
    </div>

    <div class="w-full max-w-[400px] px-6">
        <div class="ai-card rounded-[2.5rem] p-10 border-t-[3px] border-t-[#1A98C8]">

            <div class="flex flex-col items-center mb-8">
                <img src="{{ asset('logo.png') }}" alt="eGSales AI" class="h-14 w-auto mb-4 filter drop-shadow(0 0 10px rgba(26, 152, 200, 0.4))">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.4em] text-[#1A98C8]">Secure Auth Panel</span>
            </div>

            <!-- Session Status Error -->
            @if(session('status') || session('account_error') || session('error'))
            <div class="mb-4 text-[10px] text-red-400 font-bold italic uppercase animate-pulse text-center">
                {{ session('status') ?? session('account_error') ?? session('error') }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-[0.3em] mb-2 ml-1 text-[#1A98C8]">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="input-field w-full rounded-2xl px-5 py-4 text-sm @error('email') border-red-500/50 @enderror"
                        placeholder="user@egsales.ai">

                    @error('email')
                    <p class="text-[10px] text-red-400 mt-2 ml-1 font-bold italic uppercase animate-pulse">
                        [!] {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-[0.3em] mb-2 ml-1 text-[#1A98C8]">Password</label>
                    <input type="password" name="password"
                        class="input-field w-full rounded-2xl px-5 py-4 text-sm @error('password') border-red-500/50 @enderror"
                        placeholder="••••••••••••">

                    @error('password')
                    <p class="text-[10px] text-red-400 mt-2 ml-1 font-bold italic uppercase animate-pulse">
                        [!] {{ $message }}
                    </p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitBtn" class="btn-glow w-full rounded-2xl py-4 font-extrabold text-[11px] uppercase tracking-[0.2em] text-white flex items-center justify-center gap-3">
                        <span id="btnText">Initialize Session</span>
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>

            <div class="mt-10 text-center">
                <p class="text-[9px] uppercase tracking-widest text-slate-600">
                    &copy; {{ date('Y') }} eGSales AI • All Rights Reserved
                </p>
            </div>

        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        form.addEventListener('submit', function() {
            btn.disabled = true;
            btnText.innerText = "Encrypting...";
            spinner.classList.remove('hidden');
        });
    </script>

</body>

</html>
