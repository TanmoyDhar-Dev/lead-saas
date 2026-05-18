<x-app-layout>
    <x-slot name="header">
        Campaign Details
    </x-slot>

    <x-slot name="subheader">
        {{ $campaign->name }}
    </x-slot>

    <x-slot name="actions">
        <div class="flex items-center gap-3">
            @if($campaign->status === 'draft' && !$campaign->sent_to_n8n_at)
                <a href="{{ route('campaigns.confirm', $campaign) }}" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 shadow-lg shadow-blue-500/20 transition-all flex items-center gap-1.5 uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 15.3a2.1 2.1 0 010 3m-9-9a9 9 0 018.36-5.94M15 12h.01M18.3 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Review & Start Automation
                </a>
            @endif
            <a href="{{ route('campaigns.index') }}" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors shadow-sm">
                ALL CAMPAIGNS
            </a>
        </div>
    </x-slot>

    <div class="space-y-8 max-w-6xl">
        {{-- Flash Messaging --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-xs font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-2xl text-xs font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if($campaign->error_message)
            <div class="p-5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold space-y-2">
                <p class="font-bold flex items-center gap-1.5 uppercase tracking-wider text-[10px] text-red-800">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Automation Dispatch Failure
                </p>
                <p class="leading-relaxed">{{ $campaign->error_message }}</p>
            </div>
        @endif

        {{-- Overview Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Status Card --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Campaign Status</span>
                    @php
                        $statusClass = match($campaign->status) {
                            'draft' => 'bg-slate-50 text-slate-600 border-slate-200',
                            'processing' => 'bg-amber-50 text-amber-600 border-amber-200 animate-pulse',
                            'sent_to_n8n' => 'bg-blue-50 text-blue-600 border-blue-200',
                            'failed' => 'bg-red-50 text-red-600 border-red-200',
                            default => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusClass }}">
                        {{ strtoupper($campaign->status) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                    <span>Recipients Count:</span>
                    <span class="font-bold text-slate-800">{{ $campaign->campaignRecipients->count() }}</span>
                </div>
            </div>

            {{-- Mode and Window Card --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Delivery Settings</span>
                    <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">
                        Mode: <span class="text-brand-blue">{{ $campaign->delivery_mode ?: 'DRAFT' }}</span>
                    </h4>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                    <span>Search Window:</span>
                    <span class="font-bold text-slate-700 uppercase">{{ $campaign->search_window ?: 'qdr:m3' }}</span>
                </div>
            </div>

            {{-- Automation details card --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Dispatch Logs</span>
                    <h4 class="text-xs font-semibold text-slate-700">
                        {{ $campaign->sent_to_n8n_at ? 'Initiated on n8n Webhook' : 'Not dispatched yet' }}
                    </h4>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                    <span>Sent At:</span>
                    <span class="font-bold text-slate-700">
                        {{ $campaign->sent_to_n8n_at ? $campaign->sent_to_n8n_at->format('M d, Y H:i') : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Mid-section: Sender profile and Email preview --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {{-- Sender Profile preview --}}
            <div class="lg:col-span-4 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Sender Profile
                </h3>

                @if($campaign->senderIdentity)
                    @php $sender = $campaign->senderIdentity; @endphp
                    <div class="space-y-3 text-xs text-slate-600">
                        <div><span class="font-bold text-slate-400 block uppercase tracking-wider text-[9px] mb-0.5">Sender Name</span> <span class="text-slate-800 font-bold text-sm">{{ $sender->sender_name }}</span></div>
                        <div><span class="font-bold text-slate-400 block uppercase tracking-wider text-[9px] mb-0.5">Role & Company</span> <span class="text-slate-700 font-semibold">{{ $sender->sender_role }} at {{ $sender->sender_company }}</span></div>
                        <div><span class="font-bold text-slate-400 block uppercase tracking-wider text-[9px] mb-0.5">Region & Industry</span> <span class="text-slate-700 font-medium">{{ $sender->sender_region ?: '—' }} • {{ $sender->sender_industry ?: '—' }}</span></div>
                        @if($sender->sender_linkedin)
                            <div>
                                <span class="font-bold text-slate-400 block uppercase tracking-wider text-[9px] mb-0.5">LinkedIn Profile</span>
                                <a href="{{ $sender->sender_linkedin }}" target="_blank" class="text-brand-blue font-bold hover:underline truncate block max-w-full">{{ $sender->sender_linkedin }}</a>
                            </div>
                        @endif
                        @if($sender->sender_eo_chapter)
                            <div><span class="font-bold text-slate-400 block uppercase tracking-wider text-[9px] mb-0.5">EO Chapter</span> <span class="text-slate-700 font-bold">{{ $sender->sender_eo_chapter }}</span></div>
                        @endif
                    </div>
                @else
                    <div class="p-6 text-center text-slate-400 text-xs font-semibold leading-relaxed border border-dashed border-slate-200 rounded-2xl">
                        No sender profile associated with this campaign setup.
                    </div>
                @endif
            </div>

            {{-- Email Body Preview --}}
            <div class="lg:col-span-8 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col space-y-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5a2 2 0 00-2.22 0l-2.25 1.5M12 14a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                    Email Message Templates Preview
                </h3>

                <div class="space-y-4">
                    {{-- Body preview --}}
                    <div>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Email Body</span>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 font-mono text-xs text-slate-700 whitespace-pre-wrap leading-relaxed max-h-[300px] overflow-y-auto custom-scrollbar">
                            {{ $campaign->email_main_body ?: 'No email body message typed yet.' }}
                        </div>
                    </div>

                    {{-- Signature preview --}}
                    <div>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Signature Block</span>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 font-mono text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                            {{ $campaign->email_signature ?: ($campaign->senderIdentity?->email_signature ?: 'No signature block specified.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recipients Table --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Recipients Details</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Recipient list status</p>
                </div>
            </div>

            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recipient Name</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email Address</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Company & Position</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($campaign->campaignRecipients as $recipient)
                            @php $lead = $recipient->lead; @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-sm">{{ $lead?->person_name ?: '—' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-600 font-semibold">{{ $lead?->personal_email_address ?: 'Not available' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-700 font-semibold">{{ $lead?->company_name ?: '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5 truncate max-w-[250px]">{{ $lead?->position_by_apifiapi ?: ($lead?->position_by_search_param ?: '—') }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $recipClass = match($recipient->status) {
                                            'pending' => 'bg-slate-50 text-slate-400 border-slate-200',
                                            'queued' => 'bg-blue-50 text-blue-600 border-blue-200',
                                            'sent' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                                            'failed' => 'bg-red-50 text-red-600 border-red-200',
                                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold border {{ $recipClass }}">
                                        {{ strtoupper($recipient->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-xs font-semibold">
                                    No recipients found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
