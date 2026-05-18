<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div class="space-y-1">
            <label for="email" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Email Address</label>
            <input id="email" 
                class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all" 
                type="email" 
                name="email" 
                :value="old('email')" 
                placeholder="name@company.com"
                required autofocus autocomplete="username" />
            @error('email') <p class="text-red-500 text-[10px] font-bold mt-1 px-1 uppercase tracking-tight">{{ $message }}</p> @enderror
        </div>

        <!-- Password -->
        <div class="space-y-1">
            <div class="flex justify-between items-center px-1">
                <label for="password" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-[10px] font-bold text-brand-blue hover:underline uppercase tracking-widest" href="{{ route('password.request') }}">
                        Forgot?
                    </a>
                @endif
            </div>
            <input id="password" 
                class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all"
                type="password"
                name="password"
                placeholder="••••••••"
                required autocomplete="current-password" />
            @error('password') <p class="text-red-500 text-[10px] font-bold mt-1 px-1 uppercase tracking-tight">{{ $message }}</p> @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center px-1">
            <input id="remember_me" type="checkbox" class="rounded-lg border-slate-300 text-brand-blue shadow-sm focus:ring-brand-blue/20" name="remember">
            <label for="remember_me" class="ms-2 text-xs font-medium text-slate-500">Keep me logged in</label>
        </div>

        <button type="submit" class="w-full bg-navy-900 hover:bg-navy-950 text-white font-bold py-4 rounded-2xl transition-all transform active:scale-[0.98] shadow-xl shadow-navy-900/10">
            SIGN IN TO LEADFLOW
        </button>
    </form>
</x-guest-layout>
