<x-app-layout>
    <x-slot name="header">
        Connect Email
    </x-slot>

    <x-slot name="subheader">
        Connect your Gmail or Outlook mailbox through Maton so outreach sends from your own account
    </x-slot>

    <div class="space-y-8" x-data="{
        loadingProvider: null,
        errorMessage: '',
        connectedMailboxes: @js($connectedMailboxes ?? []),

        hasConnected(provider) {
            return this.connectedMailboxes.some((mailbox) => mailbox.provider === provider && mailbox.status === 'active');
        },

        setConnectedState(provider, connected) {
            this.connectedMailboxes = this.connectedMailboxes.map((mailbox) => {
                if (mailbox.provider !== provider) {
                    return mailbox;
                }

                return {
                    ...mailbox,
                    status: connected ? 'active' : 'disconnected',
                };
            });
        },

        async connect(provider) {
            this.loadingProvider = provider;
            this.errorMessage = '';

            // Open window synchronously to avoid popup blockers and main-window navigation glitches
            const popup = window.open('', '_blank', 'noopener,noreferrer,width=600,height=780');

            try {
                const response = await window.axios.post('{{ route('mailboxes.connections.create') }}', { provider });
                const authUrl = response?.data?.auth_url;

                if (!authUrl) {
                    if (popup) popup.close();
                    throw new Error('No authorization URL returned by Maton.');
                }

                if (popup) {
                    popup.location.href = authUrl;
                } else {
                    // Fallback if blocked
                    window.open(authUrl, '_blank', 'noopener,noreferrer,width=600,height=780');
                }

                const poll = window.setInterval(() => {
                    if (popup && popup.closed) {
                        window.clearInterval(poll);
                        this.verify(provider, null, null);
                        return;
                    }

                    this.verify(provider, popup, poll);
                }, 1500);

                window.setTimeout(() => {
                    if (popup && !popup.closed) {
                        popup.focus();
                    }
                }, 300);
            } catch (error) {
                this.errorMessage = error?.response?.data?.message ?? 'Unable to start the mailbox connection.';
            } finally {
                this.loadingProvider = null;
            }
        },

        async verify(provider, popup = null, poll = null) {
            try {
                const response = await window.axios.post('{{ route('mailboxes.connections.verify') }}', { provider });
                
                if (response?.data?.connected) {
                    this.setConnectedState(provider, true);
                    
                    if (poll) {
                        window.clearInterval(poll);
                    }

                    if (popup && !popup.closed) {
                        popup.close();
                    }

                    window.location.reload();
                } else if (!popup && !poll) {
                    // Final check after popup closed manually
                    this.setConnectedState(provider, false);
                    this.errorMessage = 'Connection was cancelled or not completed.';
                }
            } catch (error) {
                this.errorMessage = error?.response?.data?.message ?? 'Unable to verify mailbox connection.';
            }
        }
    }">

        <div class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-8">
            <section class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/60">
                    <h2 class="text-sm font-bold text-slate-800">Connect a mailbox</h2>
                    <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Maton handles OAuth and mailbox access</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Gmail</h3>
                                <p class="mt-1 text-sm text-slate-600">Connect a Google Workspace or Gmail mailbox for outbound outreach.</p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-700">Recommended</span>
                        </div>

                        <button
                            type="button"
                            class="mt-5 inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-bold shadow-lg transition disabled:cursor-not-allowed disabled:opacity-60"
                            :class="hasConnected('google-mail') ? 'bg-emerald-600 text-white shadow-emerald-500/20 hover:bg-emerald-500' : 'bg-brand-blue text-white shadow-blue-500/20 hover:bg-blue-600'"
                            :disabled="loadingProvider !== null || hasConnected('google-mail')"
                            @click="connect('google-mail')"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M3 12h18M3 19h18"></path></svg>
                            <span x-text="hasConnected('google-mail') ? 'Connected' : (loadingProvider === 'google-mail' ? 'Connecting...' : 'Connect Gmail')"></span>
                        </button>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Outlook</h3>
                                <p class="mt-1 text-sm text-slate-600">Connect Microsoft 365 / Outlook so sends use your native mailbox.</p>
                            </div>
                            <span class="rounded-full bg-slate-900 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white">Microsoft</span>
                        </div>

                        <button
                            type="button"
                            class="mt-5 inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-bold shadow-lg transition disabled:cursor-not-allowed disabled:opacity-60"
                            :class="hasConnected('outlook') ? 'bg-emerald-600 text-white shadow-emerald-500/20 hover:bg-emerald-500' : 'bg-slate-900 text-white shadow-slate-900/10 hover:bg-slate-800'"
                            :disabled="loadingProvider !== null || hasConnected('outlook')"
                            @click="connect('outlook')"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            <span x-text="hasConnected('outlook') ? 'Connected' : (loadingProvider === 'outlook' ? 'Connecting...' : 'Connect Outlook')"></span>
                        </button>
                    </div>

                    <p x-show="errorMessage" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" x-text="errorMessage"></p>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-800">How it works</h3>
                    <ol class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">1</span><span>Select Gmail or Outlook.</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">2</span><span>Maton creates the connection and returns an authorization URL.</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">3</span><span>You approve access in the provider popup, then the mailbox becomes available for outreach.</span></li>
                    </ol>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-800">Connected mailboxes</h3>
                    <div class="mt-4 space-y-3">
                        @if(($connectedMailboxes ?? collect())->isEmpty())
                            <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                No mailboxes connected yet.
                            </p>
                        @else
                            @foreach($connectedMailboxes as $mailbox)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ $mailbox->email_address ?: ucfirst($mailbox->provider) }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $mailbox->provider }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-widest {{ $mailbox->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $mailbox->status }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
