<x-guest-layout>
    <div class="text-center p-4">
        <div class="w-20 h-20 bg-red-50/80 backdrop-blur-md text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-red-100">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-3 tracking-tight">Account Suspended</h2>
        <p class="text-slate-500 text-sm mb-8 leading-relaxed">
            Your access plan expired on <span class="font-bold text-slate-700">{{ optional(auth()->user()->userPlan)->expiry_date ? auth()->user()->userPlan->expiry_date->format('M d, Y') : 'Unknown Date' }}</span>. 
            <br><br>
            Please contact the administrator to renew your limits and restore access to the system.
        </p>
        
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-slate-800/90 backdrop-blur-sm text-white font-bold py-3.5 px-4 rounded-xl hover:bg-slate-900 transition-all shadow-xl shadow-slate-900/20 active:scale-[0.98]">
                Secure Logout
            </button>
        </form>
    </div>
</x-guest-layout>
