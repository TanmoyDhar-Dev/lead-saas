@php
    $microsoftMailbox = auth()->user()->microsoftMailbox;
@endphp

<div x-data x-init="
    $store.integrations.configure({
        microsoftConnected: @js($microsoftMailbox !== null),
        microsoftEmail: @js($microsoftMailbox?->email_address),
        statusUrl: @js(route('integrations.status')),
        redirectUrl: @js(route('auth.microsoft.redirect', ['popup' => 1])),
        disconnectUrl: @js(url('/integrations/microsoft')),
    });
    $store.integrations.initListeners();
">
    <x-modal name="integration-microsoft" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#0078D4]/15 flex items-center justify-center">
                        <svg class="w-6 h-6" viewBox="0 0 23 23" fill="none"><path fill="#F25022" d="M1 1h10v10H1z"/><path fill="#7FBA00" d="M12 1h10v10H12z"/><path fill="#00A4EF" d="M1 12h10v10H1z"/><path fill="#FFB900" d="M12 12h10v10H12z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Microsoft Outlook</h3>
                        <p class="text-xs text-slate-500">Send bulk email via Microsoft Graph</p>
                    </div>
                </div>
                <button type="button" @click="$store.integrations.closeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div x-show="$store.integrations.loading" class="py-8 text-center">
                <svg class="animate-spin h-8 w-8 text-brand-blue mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="mt-3 text-sm text-slate-500">Checking connection status…</p>
            </div>

            <div x-show="!$store.integrations.loading">
                <div x-show="$store.integrations.errorMessage" class="mb-4 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" x-text="$store.integrations.errorMessage"></div>
                <div x-show="$store.integrations.successMessage" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700" x-text="$store.integrations.successMessage"></div>

                <div x-show="$store.integrations.microsoftConnected" class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 mb-4">
                    <div class="flex items-center gap-2 text-emerald-700 font-semibold text-sm mb-1">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Connected
                    </div>
                    <p class="text-sm text-slate-600" x-text="$store.integrations.microsoftEmail || 'Microsoft account linked'"></p>
                    <p class="text-xs text-slate-500 mt-2">Scopes: Mail.Send, User.Read, offline_access</p>
                </div>

                <div x-show="!$store.integrations.microsoftConnected" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 mb-4">
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Connect your Microsoft 365 or Outlook account to send cold emails through Graph API with refresh-token support for long-running campaigns.
                    </p>
                    <ul class="mt-3 space-y-1.5 text-xs text-slate-500">
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>Higher deliverability vs standard ESPs</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>Open &amp; click tracking via Laravel</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-brand-blue"></span>Secure OAuth — tokens stored server-side</li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 justify-end">
                    <button
                        type="button"
                        x-show="$store.integrations.microsoftConnected"
                        @click="$store.integrations.disconnectMicrosoft()"
                        :disabled="$store.integrations.connecting"
                        class="px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 rounded-xl border border-red-100 transition-colors disabled:opacity-50"
                    >
                        Disconnect
                    </button>
                    <button
                        type="button"
                        x-show="!$store.integrations.microsoftConnected"
                        @click="$store.integrations.connectMicrosoft()"
                        :disabled="$store.integrations.connecting"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-[#0078D4] hover:bg-[#106EBE] rounded-xl shadow-lg shadow-blue-500/20 transition-all disabled:opacity-60"
                    >
                        <span x-text="$store.integrations.connecting ? 'Opening Microsoft…' : 'Connect with Microsoft'"></span>
                    </button>
                    <button
                        type="button"
                        x-show="$store.integrations.microsoftConnected"
                        @click="$store.integrations.closeModal()"
                        class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
